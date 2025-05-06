<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Puzzle;
use App\Models\Submission;

class PuzzleTest extends TestCase
{
    use RefreshDatabase;

    public function testStartPuzzle()
    {
        $response = $this->postJson('/api/start-puzzle', [
            'student_name' => 'John Doe'
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['puzzle_id', 'letters']);

        $this->assertDatabaseCount('puzzles', 1);
        $this->assertDatabaseCount('submissions', 1);
    }

    public function testSubmitValidWord()
    {
        $this->postJson('/api/start-puzzle', [
            'student_name' => 'Alice'
        ]);

        $submission = Submission::first();
        $letters = Puzzle::find($submission->puzzle_id)->letters;

        // Use 2 letters from the puzzle to form a valid word (like "at" if "a" and "t" exist)
        $word = $this->generateValidWordFromLetters($letters);

        if (!$word) {
            $this->markTestSkipped("Could not generate a valid test word.");
            return;
        }

        $response = $this->postJson('/api/submit-word', [
            'puzzle_id' => $submission->puzzle_id,
            'word' => $word,
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['score']);
    }

    public function testSubmitDuplicateWord()
    {
        $this->postJson('/api/start-puzzle', [
            'student_name' => 'Bob'
        ]);

        $submission = Submission::first();
        $letters = Puzzle::find($submission->puzzle_id)->letters;

        $word = $this->generateValidWordFromLetters($letters);
        if (!$word) {
            $this->markTestSkipped("Could not generate a valid test word.");
            return;
        }

        $this->postJson('/api/submit-word', [
            'puzzle_id' => $submission->puzzle_id,
            'word' => $word,
        ]);

        $response = $this->postJson('/api/submit-word', [
            'puzzle_id' => $submission->puzzle_id,
            'word' => $word,
        ]);

        $response->assertStatus(422)
                 ->assertJson(['error' => 'Word already used']);
    }

    public function testEndPuzzle()
    {
        $this->postJson('/api/start-puzzle', [
            'student_name' => 'Carol'
        ]);

        $submission = Submission::first();

        $response = $this->postJson('/api/end-puzzle', [
            'puzzle_id' => $submission->puzzle_id
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['score', 'used_words', 'remaining_letters']);

        $this->assertTrue(Submission::find($submission->id)->is_completed);
    }

    private function generateValidWordFromLetters($letters)
    {
        // Try simple combinations like "at", "to", "in" etc.
        $validTestWords = ['at', 'to', 'in', 'it', 'on', 'is'];
        foreach ($validTestWords as $word) {
            $temp = count_chars($letters, 1);
            $canMake = true;
            foreach (count_chars($word, 1) as $ascii => $count) {
                if (!isset($temp[$ascii]) || $temp[$ascii] < $count) {
                    $canMake = false;
                    break;
                }
            }
            if ($canMake) return $word;
        }
        return null;
    }
}
