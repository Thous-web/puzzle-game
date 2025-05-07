# Laravel Word Puzzle Backend

## Overview

This Laravel backend system processes student submissions for word puzzles. It validates the words using an external API, scores the submissions, and manages a leaderboard to track the highest scorers.

## Features

- **Student Submissions**: Students can submit answers to word puzzles.
- **Word Validation**: Validates submitted words using an external API.
- **Scoring System**: Each valid word submission is scored based on predefined rules.
- **Leaderboard**: Displays the top students based on their scores.
- **Admin Panel**: Admin users can manage students, submissions, and view leaderboard.

## Requirements

Before you begin, ensure you have the following installed:

- PHP >= 8.0
- Composer
- Laravel >= 8.2
- MySQL / MariaDB
- An API key for word validation (external API)
- Run migrations php artisan migrate
- Start local server by executing php artisan serve
- Visit here http://127.0.0.1:8000/api/start to test the application
- body 
    {
    "student_name": "ihan"
    }

## Installation

### Clone the repository

```bash
git clone https://github.com/Thous-web/puzzle-game.git
cd laravel-word-puzzle-backend

