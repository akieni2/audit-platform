<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreServerAuditRunRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->tokenCan('audit-collector:submit') === true;
    }

    public function rules(): array
    {
        $limit = (int) config('server_audit.max_checks_per_run', 100);

        return [
            'run_uuid' => ['required', 'uuid'],
            'collector_version' => ['required', 'string', 'max:32'],
            'started_at' => ['nullable', 'date'],
            'completed_at' => ['required', 'date', 'after_or_equal:started_at'],
            'asset' => ['required', 'array'],
            'asset.uuid' => ['required', 'uuid'],
            'asset.hostname' => ['required', 'string', 'max:255'],
            'asset.ip_address' => ['nullable', 'ip'],
            'asset.platform' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9][a-z0-9._-]*$/'],
            'asset.type' => ['nullable', 'string', 'max:100'],
            'asset.product' => ['nullable', 'string', 'max:255'],
            'asset.product_version' => ['nullable', 'string', 'max:100'],
            'asset.operating_system' => ['nullable', 'string', 'max:255'],
            'asset.role' => ['nullable', 'string', 'max:255'],
            'asset.metadata' => ['nullable', 'array'],
            'checks' => ['required', 'array', 'min:1', 'max:'.$limit],
            'checks.*.code' => ['required', 'string', 'max:96', 'distinct', Rule::exists('server_audit_control_definitions', 'code')->where('active', true)],
            'checks.*.status' => ['required', Rule::in(['pass', 'fail', 'error', 'not_applicable'])],
            'checks.*.expected' => ['nullable', 'string', 'max:4000'],
            'checks.*.observed' => ['nullable', 'string', 'max:20000'],
            'checks.*.raw_output' => ['nullable', 'string', 'max:200000'],
            'checks.*.metadata' => ['nullable', 'array'],
        ];
    }
}
