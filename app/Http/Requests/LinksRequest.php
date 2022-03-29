<?php

namespace App\Http\Requests;

use App\Rules\SupportedLanguageCode;
use dacoto\DomainValidator\Validator\Domain as DomainValidator;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class LinksRequest extends FormRequest
{
    public const DEFAULT_LIMIT = 50;
    public const DEFAULT_OFFSET = 0;
    public const DEFAULT_WEIGHT_THRESHOLD = 0;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'query' => 'required|min:1|max:255',
            'lang_code' => ['required', new SupportedLanguageCode()],
            'domain' => ['sometimes', new DomainValidator()],
            'weightThreshold' => 'integer|min:0',
            'limit' => 'integer|min:1|max:100',
            'offset' => 'integer|min:0|max:1000',
        ];
    }

    /**
     * Add default values
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'limit' => $this->limit ?? self::DEFAULT_LIMIT,
            'offset' => $this->offset ?? self::DEFAULT_OFFSET,
            'weightThreshold' => $this->weightThreshold ?? self::DEFAULT_WEIGHT_THRESHOLD,
        ]);
    }

    /**
     * @param Validator $validator
     */
    protected function failedValidation(Validator $validator): void
    {
        $errors = (new ValidationException($validator))->errors();

        throw new HttpResponseException(
            response()->json(['status' => false, 'errors' => $errors], JsonResponse::HTTP_BAD_REQUEST)
        );
    }
}
