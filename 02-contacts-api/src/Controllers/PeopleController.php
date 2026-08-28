<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Repositories\ContactRepository;
use App\Repositories\PersonRepository;
use App\Support\Validator;

final class PeopleController
{
    public function __construct(
        private readonly PersonRepository $people,
        private readonly ContactRepository $contacts,
    ) {
    }

    /** GET /api/people — list every person together with their contacts. */
    public function index(Request $request): Response
    {
        $people = array_map(
            fn ($person) => $person->withContacts($this->contacts->forPerson($person->id))->toArray(),
            $this->people->all()
        );

        return Response::json(['data' => $people]);
    }

    /** GET /api/people/{id} — a single person with their contacts. */
    public function show(Request $request, array $args): Response
    {
        $person = $this->people->findOrFail((int) $args['id']);
        $person = $person->withContacts($this->contacts->forPerson($person->id));

        return Response::json(['data' => $person->toArray()]);
    }

    /** POST /api/people — create a person. Body: { "name": "..." } */
    public function store(Request $request): Response
    {
        $input = Validator::person($request->body());
        $person = $this->people->create($input['name']);

        return Response::json(['data' => $person->withContacts([])->toArray()], 201);
    }

    /** PUT /api/people/{id} — update a person's name. Body: { "name": "..." } */
    public function update(Request $request, array $args): Response
    {
        $id = (int) $args['id'];
        $input = Validator::person($request->body());

        $person = $this->people->update($id, $input['name']);
        $person = $person->withContacts($this->contacts->forPerson($person->id));

        return Response::json(['data' => $person->toArray()]);
    }

    /** DELETE /api/people/{id} — deletes the person and (via FK cascade) their contacts. */
    public function destroy(Request $request, array $args): Response
    {
        $this->people->delete((int) $args['id']);

        return Response::noContent();
    }
}
