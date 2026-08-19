<?php

namespace App\Services;

use App\Models\PlanningBoard;
use App\Models\PlanningColumn;
use App\Models\PlanningTask;
use App\Tenancy\TenantContext;
use Illuminate\Support\Collection;

class PlanningService
{
    public function __construct(protected TenantContext $tenantContext) {}

    public function listBoards(): Collection
    {
        return PlanningBoard::query()
            ->withCount('columns', 'tasks')
            ->latest()
            ->get();
    }

    public function showBoard(PlanningBoard $board): PlanningBoard
    {
        return $board->load([
            'columns' => fn ($q) => $q->orderBy('position'),
            'columns.tasks' => fn ($q) => $q->orderBy('position'),
        ]);
    }

    public function createBoard(array $data): PlanningBoard
    {
        $board = PlanningBoard::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
        ]);

        $board->columns()->create(['name' => 'Backlog', 'position' => 0]);
        $board->columns()->create(['name' => 'In Progress', 'position' => 1]);
        $board->columns()->create(['name' => 'Done', 'position' => 2]);

        return $board;
    }

    public function updateBoard(PlanningBoard $board, array $data): PlanningBoard
    {
        $board->update([
            'name' => $data['name'],
            'description' => array_key_exists('description', $data) ? $data['description'] : $board->description,
        ]);

        return $board;
    }

    public function deleteBoard(PlanningBoard $board): void
    {
        $board->delete();
    }

    public function addColumn(PlanningBoard $board, array $data): PlanningColumn
    {
        $this->assertBoardAccess($board);

        return $board->columns()->create([
            'name' => $data['name'],
            'position' => $board->columns()->count(),
        ]);
    }

    public function updateColumn(PlanningColumn $column, array $data): PlanningColumn
    {
        $this->assertColumnAccess($column);

        $column->update([
            'name' => $data['name'],
            'position' => $data['position'] ?? $column->position,
        ]);

        return $column;
    }

    public function deleteColumn(PlanningColumn $column): void
    {
        $this->assertColumnAccess($column);

        $column->delete();
    }

    public function addTask(PlanningColumn $column, array $data): PlanningTask
    {
        $this->assertColumnAccess($column);

        return $column->tasks()->create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'start_date' => $data['start_date'] ?? null,
            'due_date' => $data['due_date'] ?? null,
            'position' => $column->tasks()->count(),
        ]);
    }

    public function updateTask(PlanningTask $task, array $data): PlanningTask
    {
        $this->assertTaskAccess($task);

        $task->update($data);

        return $task;
    }

    public function deleteTask(PlanningTask $task): void
    {
        $this->assertTaskAccess($task);

        $task->delete();
    }

    public function moveTask(PlanningTask $task, ?string $columnId, ?int $position): PlanningTask
    {
        $this->assertTaskAccess($task);

        $currentColumn = $task->column;

        if ($columnId && $columnId !== $currentColumn->id) {
            $target = PlanningColumn::query()->findOrFail($columnId);
            abort_unless($target->board_id === $currentColumn->board_id, 422, 'Target column must belong to the same board.');

            $task->column_id = $target->id;
        }

        $task->position = $position ?? 0;
        $task->save();

        $this->reindexColumn($task->column_id);

        return $task->refresh();
    }

    protected function assertBoardAccess(PlanningBoard $board): void
    {
        abort_unless($board->tenant_id === $this->tenantContext->id(), 404, 'Board not found.');
    }

    protected function assertColumnAccess(PlanningColumn $column): void
    {
        abort_unless($column->board, 404, 'Column not found.');
    }

    protected function assertTaskAccess(PlanningTask $task): void
    {
        abort_unless($task->column?->board, 404, 'Task not found.');
    }

    protected function reindexColumn(string $columnId): void
    {
        PlanningTask::query()
            ->where('column_id', $columnId)
            ->orderBy('position')
            ->orderBy('created_at')
            ->get()
            ->each(fn (PlanningTask $task, int $index) => $task->updateQuietly(['position' => $index]));
    }
}
