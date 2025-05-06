<?php

namespace App\Http\Controllers;

use App\Models\Puzzle;
use App\Models\Submission;
use App\Models\Leaderboard;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PuzzleController extends Controller
{
    /**
     * Starts a new puzzle for a student.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function startPuzzle(Request $request)
    {
        $request->validate([
            'student_name' => 'required|string|max:255',
        ]);

        $letters = Str::lower(Str::of(Str::random(28))->replaceMatches('/[^a-z]/', '')->substr(0, 14));

        $puzzle = Puzzle::create(['letters' => $letters]);

        $submission = Submission::create([
            'puzzle_id' => $puzzle->id,
            'student_name' => $request->student_name,
            'remaining_letters' => json_encode(count_chars($letters, 1)),
            'used_words' => json_encode([]),
            'score' => 0,
        ]);

        return response()->json([
            'puzzle_id' => $puzzle->id,
            'letters' => $letters,
        ]);
    }

    /**
     * Submits a word for the current puzzle.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function submitWord(Request $request)
    {
        $request->validate([
            'puzzle_id' => 'required|exists:submissions,puzzle_id',
            'word' => 'required|string',
        ]);

        $submission = Submission::where('puzzle_id', $request->puzzle_id)
            ->where('is_completed', false)
            ->firstOrFail();

        $word = strtolower(trim($request->word));
        $usedWords = json_decode($submission->used_words, true);
        $remaining = json_decode($submission->remaining_letters, true);

        if (in_array($word, $usedWords)) {
            return response()->json(['error' => 'Word already used'], 422);
        }

        if (!$this->isValidEnglishWord($word)) {
            return response()->json(['error' => 'Invalid English word'], 422);
        }

        // Count each letter used in the word
        foreach (count_chars($word, 1) as $ascii => $count) {
            if (empty($remaining[$ascii]) || $remaining[$ascii] < $count) {
                return response()->json(['error' => 'Not enough letters'], 422);
            }
            $remaining[$ascii] -= $count;
            if ($remaining[$ascii] <= 0) {
                unset($remaining[$ascii]);
            }
        }

        $usedWords[] = $word;
        $submission->used_words = json_encode($usedWords);
        $submission->remaining_letters = json_encode($remaining);
        $submission->score += strlen($word);
        $submission->save();
        Leaderboard::firstOrCreate(
            ['word' => $word],
            ['score' => strlen($word)]
        );

        return response()->json(['score' => $submission->score]);
    }

    /**
     * Ends the puzzle and returns the score and remaining letters.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function endPuzzle(Request $request)
    {
        $submission = Submission::where('puzzle_id', $request->puzzle_id)->firstOrFail();
        $submission->is_completed = true;
        $submission->save();

        return response()->json([
            'score' => $submission->score,
            'used_words' => json_decode($submission->used_words),
            'remaining_letters' => $this->remainingLettersAsString($submission->remaining_letters),
        ]);
    }

    /**
     * Returns the leaderboard with the top 10 scores.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function leaderboard()
    {
        return response()->json(
            Leaderboard::orderByDesc('score')->limit(10)->get()
        );
    }

    /**
     * Checks if the word is a valid English word.
     *
     * @param string $word
     * @return bool
     */
    private function isValidEnglishWord($word)
    {
        try {
            $response = Http::get("https://api.dictionaryapi.dev/api/v2/entries/en/{$word}");
            return $response->ok();
        } catch (\Exception $e) {
            Log::error("Dictionary check failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Converts the remaining letters from JSON to a string.
     *
     * @param string $remainingLettersJson
     * @return array
     */
    private function remainingLettersAsString($remainingLettersJson)
    {
        $remaining = json_decode($remainingLettersJson, true);
        $result = '';
        foreach ($remaining as $ascii => $count) {
            $result .= str_repeat(chr($ascii), $count);
        }
        return str_split($result);
    }
}
