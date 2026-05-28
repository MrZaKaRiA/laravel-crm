@foreach ($customAttributes as $attribute)
    @php
        $validations = [];

        if ($attribute->is_required) {
            $validations[] = 'required';
        }

        if ($attribute->type == 'price') {
            $validations[] = 'decimal';
        }

        $validations[] = $attribute->validation;

        $validations = implode('|', array_filter($validations));

        $aliases = [
            'lead_source_id' => 'source', 'lead_type_id' => 'type',
            'lead_pipeline_id' => 'pipeline', 'lead_pipeline_stage_id' => 'stage',
            'user_id' => 'sales-owner', 'organization_id' => 'organization',
            'person_id' => 'person',
        ];
        $key = 'installer::app.seeders.attributes.'.$attribute->entity_type.'.'.($aliases[$attribute->code] ?? str_replace('_', '-', $attribute->code));
        $label = trans($key);
        if ($label === $key) {
            $label = $attribute->name;
        }
    @endphp

    <x-admin::form.control-group class="mb-2.5 w-full">
        <x-admin::form.control-group.label
            for="{{ $attribute->code }}"
            :class="$attribute->is_required ? 'required' : ''"
        >
            {{ $label }}

            @if ($attribute->type == 'price')
                <span class="currency-code">({{ core()->currencySymbol(config('app.currency')) }})</span>
            @endif
        </x-admin::form.control-group.label>

        @if (isset($attribute))
            <x-admin::attributes.edit.index
                :attribute="$attribute"
                :validations="$validations"
                :value="isset($entity) ? $entity[$attribute->code] : null"
            />
        @endif

        <x-admin::form.control-group.error :control-name="$attribute->code" />
    </x-admin::form.control-group>
@endforeach