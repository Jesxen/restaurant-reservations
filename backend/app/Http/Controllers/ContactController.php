<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactRequest;
use App\Mail\ContactMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    /**
     * Receive a contact-form message and email it to the restaurant.
     */
    public function store(ContactRequest $request): JsonResponse
    {
        $data = $request->validated();

        Mail::to(config('mail.contact_to', config('mail.from.address')))
            ->send(new ContactMessage($data['nombre'], $data['email'], $data['mensaje']));

        return response()->json([
            'message' => 'Mensaje enviado. Te responderemos lo antes posible.',
        ], 202);
    }
}
