<?php

namespace App\Http\Resources\Concerns;

trait FormatsAssignedBy
{
    /**
     * @return array{id: int, name: string|null}|null
     */
    private function assignedBySummary(): ?array
    {
        if ($this->assignedBy === null) {
            return null;
        }

        return [
            'id' => (int) $this->assignedBy->PK_USUARIO,
            'name' => $this->assignedBy->NO_USUARIO,
        ];
    }
}
