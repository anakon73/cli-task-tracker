<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class TaskCommand extends Command
{

    protected $signature = 'task {action} {param1?} {param2?}';

    protected $description = 'Command description';

    public function handle()
    {
        $action = $this->argument('action');
        $param1 = $this->argument('param1');
        $param2 = $this->argument('param2');

        try {
            $this->validateAction($action);

            match ($action) {
                'add' => $this->validateAndAddTask($param1),
                'list' => $this->listTasks($param1),
                'delete' => $this->validateAndDeleteTask($param1),
                'update' => $this->validateAndUpdateTask($param1, $param2),
                'mark-done' => $this->validateAndMarkDone($param1),
                'mark-in-progress' => $this->validateAndMarkInProgress($param1),
                'mark-todo' => $this->validateAndMarkTodo($param1),
            };
        } catch (\InvalidArgumentException $e) {
            $this->error("Error: " . $e->getMessage());
            return 1;
        } catch (\Exception $e) {
            $this->error("Unexpected error: " . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function getTasks(): array
    {
        try {
            if (!Storage::disk('local')->exists('tasks.json')) {
                return [];
            }

            $content = Storage::disk('local')->get('tasks.json');
            if (empty($content)) {
                return [];
            }

            $tasks = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \RuntimeException('Invalid JSON in tasks file: ' . json_last_error_msg());
            }

            return is_array($tasks) ? $tasks : [];
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to read tasks file: ' . $e->getMessage());
        }
    }

    private function saveTasks(array $tasks): void
    {
        try {
            $json = json_encode($tasks, JSON_PRETTY_PRINT);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \RuntimeException('Failed to encode tasks to JSON: ' . json_last_error_msg());
            }

            if (!Storage::disk('local')->put('tasks.json', $json)) {
                throw new \RuntimeException('Failed to write tasks file');
            }
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to save tasks: ' . $e->getMessage());
        }
    }

    private function listTasks(?string $status): void
    {
        $tasks = $this->getTasks();
        if ($status) {
            $tasks = array_filter($tasks, fn($task) => $task['status'] === $status);
        }
        $this->info(json_encode($tasks, JSON_PRETTY_PRINT));
    }

    private function addTask(string $description): void
    {
        $tasks = $this->getTasks();

        $newId = empty($tasks) ? 1 : max(array_column($tasks, 'id')) + 1;

        $tasks[] = [
            'id' => $newId,
            'description' => $description,
            'status' => 'todo',
            'created_at' => now()->toISOString(),
            'updated_at' => now()->toISOString(),
        ];
        $this->saveTasks($tasks);
        $this->info('Task added successfully');
    }

    private function deleteTask(int $id): void
    {
        $tasks = $this->getTasks();
        $originalCount = count($tasks);

        $tasks = array_filter($tasks, fn($task) => $task['id'] !== $id);

        if (count($tasks) === $originalCount) {
            throw new \InvalidArgumentException("Task with ID $id not found");
        }

        $this->saveTasks(array_values($tasks));
        $this->info('Task deleted successfully');
    }

    private function updateTask(int $id, string $description): void
    {
        $tasks = $this->getTasks();
        $found = false;

        $tasks = array_map(function ($task) use ($id, $description, &$found) {
            if ($task['id'] === $id) {
                $found = true;
                $task['description'] = $description;
                $task['updated_at'] = now()->toISOString();
            }
            return $task;
        }, $tasks);

        if (!$found) {
            throw new \InvalidArgumentException("Task with ID $id not found");
        }

        $this->saveTasks($tasks);
        $this->info('Task updated successfully');
    }

    private function markDone(int $id): void
    {
        $tasks = $this->getTasks();
        $found = false;

        $tasks = array_map(function ($task) use ($id, &$found) {
            if ($task['id'] === $id) {
                $found = true;
                $task['status'] = 'done';
                $task['updated_at'] = now()->toISOString();
            }
            return $task;
        }, $tasks);

        if (!$found) {
            throw new \InvalidArgumentException("Task with ID $id not found");
        }

        $this->saveTasks($tasks);
        $this->info('Task marked as done successfully');
    }

    private function markInProgress(int $id): void
    {
        $tasks = $this->getTasks();
        $found = false;

        $tasks = array_map(function ($task) use ($id, &$found) {
            if ($task['id'] === $id) {
                $found = true;
                $task['status'] = 'in-progress';
                $task['updated_at'] = now()->toISOString();
            }
            return $task;
        }, $tasks);

        if (!$found) {
            throw new \InvalidArgumentException("Task with ID $id not found");
        }

        $this->saveTasks($tasks);
        $this->info('Task marked as in-progress successfully');
    }

    private function markTodo(int $id): void
    {
        $tasks = $this->getTasks();
        $found = false;

        $tasks = array_map(function ($task) use ($id, &$found) {
            if ($task['id'] === $id) {
                $found = true;
                $task['status'] = 'todo';
                $task['updated_at'] = now()->toISOString();
            }
            return $task;
        }, $tasks);

        if (!$found) {
            throw new \InvalidArgumentException("Task with ID $id not found");
        }

        $this->saveTasks($tasks);
        $this->info('Task marked as todo successfully');
    }

    private function validateAction(string $action): void
    {
        $validActions = ['add', 'list', 'delete', 'update', 'mark-done', 'mark-in-progress', 'mark-todo'];

        if (empty($action)) {
            throw new \InvalidArgumentException('Action is required. Available actions: ' . implode(', ', $validActions));
        }

        if (!in_array($action, $validActions)) {
            throw new \InvalidArgumentException("Invalid action '$action'. Available actions: " . implode(', ', $validActions));
        }
    }

    private function validateAndAddTask(?string $description): void
    {
        if (empty($description)) {
            throw new \InvalidArgumentException('Task description is required for add action');
        }

        if (strlen(trim($description)) === 0) {
            throw new \InvalidArgumentException('Task description cannot be empty');
        }

        $this->addTask(trim($description));
    }

    private function validateAndDeleteTask(?string $id): void
    {
        if ($id === null) {
            throw new \InvalidArgumentException('Task ID is required for delete action');
        }

        if (!is_numeric($id) || (int)$id <= 0) {
            throw new \InvalidArgumentException('Task ID must be a positive integer');
        }

        $this->deleteTask((int)$id);
    }

    private function validateAndUpdateTask(?string $id, ?string $description): void
    {
        if ($id === null) {
            throw new \InvalidArgumentException('Task ID is required for update action');
        }

        if (!is_numeric($id) || (int)$id <= 0) {
            throw new \InvalidArgumentException('Task ID must be a positive integer');
        }

        if (empty($description)) {
            throw new \InvalidArgumentException('Task description is required for update action');
        }

        if (strlen(trim($description)) === 0) {
            throw new \InvalidArgumentException('Task description cannot be empty');
        }

        $this->updateTask((int)$id, trim($description));
    }

    private function validateAndMarkDone(?string $id): void
    {
        if ($id === null) {
            throw new \InvalidArgumentException('Task ID is required for mark-done action');
        }

        if (!is_numeric($id) || (int)$id <= 0) {
            throw new \InvalidArgumentException('Task ID must be a positive integer');
        }

        $this->markDone((int)$id);
    }

    private function validateAndMarkInProgress(?string $id): void
    {
        if ($id === null) {
            throw new \InvalidArgumentException('Task ID is required for mark-in-progress action');
        }

        if (!is_numeric($id) || (int)$id <= 0) {
            throw new \InvalidArgumentException('Task ID must be a positive integer');
        }

        $this->markInProgress((int)$id);
    }

    private function validateAndMarkTodo(?string $id): void
    {
        if ($id === null) {
            throw new \InvalidArgumentException('Task ID is required for mark-todo action');
        }

        if (!is_numeric($id) || (int)$id <= 0) {
            throw new \InvalidArgumentException('Task ID must be a positive integer');
        }

        $this->markTodo((int)$id);
    }
}
