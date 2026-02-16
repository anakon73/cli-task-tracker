# CLI Task Tracker

A simple command-line task management system built with Laravel Artisan commands. This application allows you to manage tasks directly from your terminal with basic CRUD operations and status tracking.

## Features

-   **Add tasks** with descriptions
-   **List tasks** with optional status filtering
-   **Update task descriptions**
-   **Delete tasks**
-   **Mark tasks** with different statuses:
    -   `todo` (default)
    -   `in-progress`
    -   `done`
-   **JSON storage** using Laravel's local filesystem
-   **Automatic timestamps** for creation and updates

## Installation

1. Clone the repository
2. Install dependencies:
    ```bash
    composer install
    ```
3. Set up your Laravel environment:
    ```bash
    cp .env.example .env
    php artisan key:generate
    ```
4. Ensure the `storage/app` directory is writable:
    ```bash
    php artisan storage:link
    ```

## Usage

The task tracker uses the `php artisan task` command with the following syntax:

```bash
php artisan task <action> [param1] [param2]
```

### Available Actions

#### Add a Task

```bash
php artisan task add "Task description"
```

_Creates a new task with `todo` status_

#### List Tasks

```bash
# List all tasks
php artisan task list

# List tasks by status
php artisan task list todo
php artisan task list in-progress
php artisan task list done
```

#### Update a Task

```bash
php artisan task update <task_id> "New task description"
```

#### Delete a Task

```bash
php artisan task delete <task_id>
```

#### Mark Task Status

```bash
# Mark as done
php artisan task mark-done <task_id>

# Mark as in progress
php artisan task mark-in-progress <task_id>

# Mark as todo
php artisan task mark-todo <task_id>
```

## Task Structure

Each task is stored as a JSON object with the following structure:

```json
{
    "id": 1,
    "description": "Complete project documentation",
    "status": "in-progress",
    "created_at": "2024-01-15T10:30:00.000000Z",
    "updated_at": "2024-01-15T14:20:00.000000Z"
}
```

## Storage

Tasks are stored in `storage/app/tasks.json` as a JSON array. The file is automatically created when you add your first task.

## Examples

### Basic Workflow

```bash
# Add some tasks
php artisan task add "Write project documentation"
php artisan task add "Review pull requests"
php artisan task add "Deploy to production"

# List all tasks
php artisan task list

# Update a task status
php artisan task mark-in-progress 1

# Update task description
php artisan task update 2 "Review and merge pull requests"

# Mark task as complete
php artisan task mark-done 2

# Delete a task
php artisan task delete 3

# Filter tasks by status
php artisan task list todo
```

### Output Examples

**Listing tasks:**

```json
[
    {
        "id": 1,
        "description": "Write project documentation",
        "status": "in-progress",
        "created_at": "2024-01-15T10:30:00.000000Z",
        "updated_at": "2024-01-15T14:20:00.000000Z"
    },
    {
        "id": 2,
        "description": "Review and merge pull requests",
        "status": "done",
        "created_at": "2024-01-15T11:00:00.000000Z",
        "updated_at": "2024-01-15T15:45:00.000000Z"
    }
]
```

## Error Handling

The command includes basic error handling:

-   Invalid task IDs will show appropriate error messages
-   Missing required parameters will trigger usage help
-   File system errors are caught and displayed

## Development

### Command Structure

The `TaskCommand` class is located at `app/Console/Commands/TaskCommand.php` and follows Laravel Artisan command conventions:

-   **Signature**: `task {action} {param1?} {param2?}`
-   **Description**: Manages tasks with CRUD operations
-   **Methods**: Private methods for each action type

### Extending the Functionality

To add new features:

1. Add the new action to the `match` statement in the `handle()` method
2. Implement the corresponding private method
3. Update this README with the new functionality

## Roadmap.sh

https://roadmap.sh/projects/task-tracker

## License

This project is open-sourced software licensed under the MIT license.
