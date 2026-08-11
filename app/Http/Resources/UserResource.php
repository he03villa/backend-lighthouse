<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'timezone' => $this->timezone,
            'locale' => $this->locale,
            'is_super_admin' => (bool) $this->is_super_admin,
            'created_at' => $this->created_at?->toISOString(),
            'tenants' => $this->whenLoaded('tenants', function () use ($request) {
                return $this->access['tenants']->map(function (array $membership) use ($request) {
                    return array_merge(
                        (new TenantResource($membership['tenant']))->resolve($request),
                        [
                            'roles' => $membership['roles'],
                            'permissions' => $membership['permissions'],
                        ],
                    );
                })->values();
            }),
            'roles' => $this->whenLoaded('access', fn () => $this->access['roles']),
            'permissions' => $this->whenLoaded('access', fn () => $this->access['permissions']),
        ];
    }
}
