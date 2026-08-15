<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Module;
use App\Models\Program;
use App\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProgramService
{
    public function __construct(protected TenantContext $tenantContext) {}

    public function storeThumbnail(UploadedFile $file): string
    {
        $tenantId = $this->tenantContext->id() ?? 'system';
        $path = $file->store('programs/'.$tenantId, 'public');

        return Storage::disk('public')->url($path);
    }
    public function list(): Collection
    {
        return Program::query()->with('modules.activities')->get();
    }

    public function create(array $data): Program
    {
        return DB::transaction(function () use ($data) {
            $modules = $data['modules'] ?? [];
            unset($data['modules']);

            $program = Program::create($data);
            $this->syncModules($program, $modules);

            return $program->load('modules.activities');
        });
    }

    public function update(Program $program, array $data): Program
    {
        return DB::transaction(function () use ($program, $data) {
            $modules = $data['modules'] ?? null;
            unset($data['modules']);

            $program->update($data);

            if ($modules !== null) {
                $program->modules()->delete();
                $this->syncModules($program, $modules);
            }

            return $program->load('modules.activities');
        });
    }

    public function delete(Program $program): void
    {
        $program->delete();
    }

    public function publish(Program $program): Program
    {
        $program->update(['is_published' => true]);

        return $program;
    }

    public function unpublish(Program $program): Program
    {
        $program->update(['is_published' => false]);

        return $program;
    }

    public function addModule(Program $program, array $data): Module
    {
        return $program->modules()->create($data);
    }

    public function updateModule(Program $program, Module $module, array $data): Module
    {
        abort_unless($module->program_id === $program->id, 404, 'Module not found in this program.');

        $module->update($data);

        return $module;
    }

    public function deleteModule(Program $program, Module $module): void
    {
        abort_unless($module->program_id === $program->id, 404, 'Module not found in this program.');

        $module->delete();
    }

    public function addActivity(Module $module, array $data): Activity
    {
        return $module->activities()->create($data);
    }

    public function updateActivity(Module $module, Activity $activity, array $data): Activity
    {
        abort_unless($activity->module_id === $module->id, 404, 'Activity not found in this module.');

        $activity->update($data);

        return $activity;
    }

    public function deleteActivity(Module $module, Activity $activity): void
    {
        abort_unless($activity->module_id === $module->id, 404, 'Activity not found in this module.');

        $activity->delete();
    }

    protected function syncModules(Program $program, array $modules): void
    {
        foreach ($modules as $index => $module) {
            $activities = $module['activities'] ?? [];
            unset($module['activities']);

            $module['order'] = $module['order'] ?? $index;

            $created = $program->modules()->create($module);
            $this->syncActivities($created, $activities);
        }
    }

    protected function syncActivities(Module $module, array $activities): void
    {
        foreach ($activities as $index => $activity) {
            $activity['order'] = $activity['order'] ?? $index;

            $module->activities()->create($activity);
        }
    }
}
