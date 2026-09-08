<?php

namespace App\Services;

use App\Models\Group;
use App\Models\Participant;
use Illuminate\Pagination\LengthAwarePaginator;

class GroupService
{
    public function list(): LengthAwarePaginator
    {
        return Group::query()->with('participants')->paginate(20);
    }

    public function create(array $data): Group
    {
        return Group::create($data);
    }

    public function update(Group $group, array $data): Group
    {
        $group->update($data);

        return $group;
    }

    public function delete(Group $group): void
    {
        $group->delete();
    }

    public function addMember(Group $group, Participant $participant): Group
    {
        $group->participants()->syncWithoutDetaching([$participant->id]);

        return $group->load('participants');
    }

    public function removeMember(Group $group, Participant $participant): Group
    {
        $group->participants()->detach($participant->id);

        return $group->load('participants');
    }
}
