<?php

namespace App\Http\Resources;

use App\Models\ProfessionalCertification;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ProfessionalCertification
 */
class CertificationOptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->PK_CERTIFICACAO_PROFISSIONAL,
            'name' => $this->NO_CERTIFICACAO_PROFISSIONAL,
            'issuer' => $this->NO_EMISSOR_CERTIFICACAO,
            'knowledge_domain' => $this->knowledgeDomain(),
        ];
    }

    private function knowledgeDomain(): ?array
    {
        if ($this->knowledgeDomain === null) {
            return null;
        }

        return [
            'id' => $this->knowledgeDomain->PK_DOMINIO_CONHECIMENTO,
            'name_pt' => $this->knowledgeDomain->NO_DOMINIO_CONHECIMENTO_PT,
            'name_en' => $this->knowledgeDomain->NO_DOMINIO_CONHECIMENTO_EN,
        ];
    }
}
