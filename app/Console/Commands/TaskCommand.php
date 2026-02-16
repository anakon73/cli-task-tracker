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
            match ($action) {
                'add' => $this->addTask($param1),
                'list' => $this->listTasks($param1),
                'delete' => $this->deleteTask((int)$param1),
                'update' => $this->updateTask((int)$param1, $param2),
                'mark-done' => $this->markDone((int)$param1),
                'mark-in-progress' => $this->markInProgress((int)$param1),
                'mark-todo' => $this->markTodo((int)$param1),
            };
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    private function getTasks(): array
    {
        if (!Storage::disk('local')->exists('tasks.json')) {
            return [];
        }
        return json_decode(Storage::disk('local')->get('tasks.json'), true) ?? [];
    }

    private function saveTasks(array $tasks): void
    {
        Storage::disk('local')->put('tasks.json', json_encode($tasks, JSON_PRETTY_PRINT));
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
        $tasks[] = [
            'id' => max(array_column($tasks, 'id')) + 1,
            'description' => $description,
            'status' => 'todo',
            'created_at' => now(),
            'updated_at' => now(),
        ];
        $this->saveTasks($tasks);
        $this->info('Task added successfully');
    }

    private function deleteTask(int $id): void
    {
        $tasks = $this->getTasks();
        $tasks = array_filter($tasks, fn($task) => $task['id'] !== $id);
        $this->saveTasks($tasks);
        $this->info('Task deleted successfully');
    }

    private function updateTask(int $id, string $description): void
    {
        $tasks = $this->getTasks();
        $tasks = array_map(function ($task) use ($id, $description) {
            if ($task['id'] === $id) {
                $task['description'] = $description;
                $task['updated_at'] = now();
            }
            return $task;
        }, $tasks);
        $this->saveTasks($tasks);
        $this->info('Task updated successfully');
    }

    private function markDone(int $id): void
    {
        $tasks = $this->getTasks();
        $tasks = array_map(function ($task) use ($id) {
            if ($task['id'] === $id) {
                $task['status'] = 'done';
                $task['updated_at'] = now();
            }
            return $task;
        }, $tasks);
        $this->saveTasks($tasks);
        $this->info('Task marked as done successfully');
    }

    private function markInProgress(int $id): void
    {
        $tasks = $this->getTasks();
        $tasks = array_map(function ($task) use ($id) {
            if ($task['id'] === $id) {
                $task['status'] = 'in-progress';
                $task['updated_at'] = now();
            }
            return $task;
        }, $tasks);
        $this->saveTasks($tasks);
        $this->info('Task marked as in-progress successfully');
    }

    private function markTodo(int $id): void
    {
        $tasks = $this->getTasks();
        $tasks = array_map(function ($task) use ($id) {
            if ($task['id'] === $id) {
                $task['status'] = 'todo';
                $task['updated_at'] = now();
            }
            return $task;
        }, $tasks);
        $this->saveTasks($tasks);
        $this->info('Task marked as todo successfully');
    }
}
