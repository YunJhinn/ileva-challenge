<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Repositories\ContactRepository;
use App\Repositories\PersonRepository;
use App\Support\Validator;

final class ContactsController
{
    public function __construct(
        private readonly ContactRepository $contacts,
        private readonly PersonRepository $people,
    ) {
    }

    /**
     * POST /api/people/{personId}/contacts — add a contact to a person.
     * Body: { "type": "phone" | "email" | "whatsapp", "value": "..." }
     */
    public function store(Request $request, array $args): Response
    {
        $personId = (int) $args['personId'];
        $this->people->findOrFail($personId); // 404 early if the person doesn't exist

        $input = Validator::contact($request->body());
        $contact = $this->contacts->create($personId, $input['type'], $input['value']);

        return Response::json(['data' => $contact->toArray()], 201);
    }

    /** GET /api/contacts/{id} */
    public function show(Request $request, array $args): Response
    {
        $contact = $this->contacts->findOrFail((int) $args['id']);

        return Response::json(['data' => $contact->toArray()]);
    }

    /** PUT /api/contacts/{id} — Body: { "type": "...", "value": "..." } */
    public function update(Request $request, array $args): Response
    {
        $input = Validator::contact($request->body());
        $contact = $this->contacts->update((int) $args['id'], $input['type'], $input['value']);

        return Response::json(['data' => $contact->toArray()]);
    }

    /** DELETE /api/contacts/{id} */
    public function destroy(Request $request, array $args): Response
    {
        $this->contacts->delete((int) $args['id']);

        return Response::noContent();
    }
}
