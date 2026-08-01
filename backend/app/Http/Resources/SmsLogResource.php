<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SmsLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'recipient' => $this->recipient,
            'message' => $this->message,
            'status' => $this->status,
            'attempts' => $this->attempts,
            'cost' => (float) $this->cost,
            'duration_ms' => $this->duration_ms,
            'message_id' => $this->message_id,
            'provider_status' => $this->provider_status,
            'http_status' => $this->http_status,
            'failure_reason' => $this->failure_reason,
            'provider' => $this->whenLoaded('provider', fn () => [
                'id' => $this->provider->id,
                'name' => $this->provider->name,
                'key' => $this->provider->key,
            ]),
            'template' => $this->whenLoaded('template', fn () => [
                'id' => $this->template->id,
                'title' => $this->template->title,
            ]),
            'creator' => $this->whenLoaded('creator', fn () => [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ]),
            'request_payload' => $this->when($request->routeIs('sms.logs.show'), fn () => $this->request_payload),
            'response_payload' => $this->when($request->routeIs('sms.logs.show'), fn () => $this->response_payload),
            'provider_response' => $this->when($request->routeIs('sms.logs.show'), fn () => $this->provider_response),
            'failures' => $this->whenLoaded('failures'),
            'sent_at' => $this->sent_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
