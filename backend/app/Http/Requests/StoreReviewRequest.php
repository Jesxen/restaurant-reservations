<?php

namespace App\Http\Requests;

use App\Models\Reserva;
use App\Models\Review;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Submission of a public review. Only authenticated clients with at least one
 * completed reservation may post, and only once per completed reservation.
 *
 * Eligibility is enforced here (after basic validation) AND by ReviewPolicy@create
 * in the controller, for defence in depth.
 */
class StoreReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Must be authenticated; finer-grained eligibility is checked in
        // withValidator() and the controller policy.
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comentario' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'rating.required' => 'La valoración es obligatoria.',
            'rating.integer' => 'La valoración no es válida.',
            'rating.min' => 'La valoración debe estar entre 1 y 5 estrellas.',
            'rating.max' => 'La valoración debe estar entre 1 y 5 estrellas.',
            'comentario.required' => 'El comentario es obligatorio.',
            'comentario.min' => 'El comentario es demasiado corto.',
            'comentario.max' => 'El comentario no puede superar los 1000 caracteres.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $user = $this->user();

            // Must have at least one completed reservation to be eligible.
            $completadas = Reserva::query()
                ->where('user_id', $user->id)
                ->where('estado', 'completada')
                ->pluck('id');

            if ($completadas->isEmpty()) {
                $validator->errors()->add(
                    'reserva',
                    'Solo puedes dejar una reseña si has completado al menos una reserva con nosotros.',
                );

                return;
            }

            // One review per user per completed reservation: blocked once the
            // client has a review for every completed reservation they have.
            $yaReseñadas = Review::query()
                ->where('user_id', $user->id)
                ->whereIn('reserva_id', $completadas)
                ->pluck('reserva_id');

            if ($yaReseñadas->count() >= $completadas->count()) {
                $validator->errors()->add(
                    'reserva',
                    'Ya has dejado una reseña por tus reservas completadas. ¡Gracias!',
                );
            }
        });
    }

    /**
     * The earliest completed reservation the client has not yet reviewed.
     */
    public function reservaParaReseñar(): ?Reserva
    {
        $user = $this->user();

        $reseñadas = Review::query()
            ->where('user_id', $user->id)
            ->pluck('reserva_id')
            ->filter()
            ->all();

        return Reserva::query()
            ->where('user_id', $user->id)
            ->where('estado', 'completada')
            ->whereNotIn('id', $reseñadas)
            ->orderBy('fecha')
            ->first();
    }
}
