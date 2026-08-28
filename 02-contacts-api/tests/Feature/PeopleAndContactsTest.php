<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Controllers\ContactsController;
use App\Controllers\PeopleController;
use App\Http\Request;
use App\Repositories\ContactRepository;
use App\Repositories\PersonRepository;
use App\Support\NotFoundException;
use Tests\TestCase;

/**
 * These exercise the controllers directly against a real (in-memory) SQLite
 * database - no HTTP layer involved - so they run fast with no server
 * needed, while still covering the full create/read/update/delete flow.
 */
final class PeopleAndContactsTest extends TestCase
{
    private PeopleController $people;
    private ContactsController $contacts;

    protected function setUp(): void
    {
        parent::setUp();

        $personRepository = new PersonRepository($this->db);
        $contactRepository = new ContactRepository($this->db);
        $this->people = new PeopleController($personRepository, $contactRepository);
        $this->contacts = new ContactsController($contactRepository, $personRepository);
    }

    public function testFullPersonAndContactLifecycle(): int
    {
        // Create a person.
        $created = $this->decode($this->people->store(Request::create('POST', body: ['name' => 'Ada Lovelace'])));
        $personId = $created['data']['id'];
        $this->assertSame('Ada Lovelace', $created['data']['name']);
        $this->assertSame([], $created['data']['contacts']);

        // Add two contacts.
        $phone = $this->decode($this->contacts->store(
            Request::create('POST', body: ['type' => 'phone', 'value' => '+55 62 90000-0000']),
            ['personId' => (string) $personId]
        ));
        $this->assertSame('phone', $phone['data']['type']);

        $email = $this->decode($this->contacts->store(
            Request::create('POST', body: ['type' => 'email', 'value' => 'ada@example.com']),
            ['personId' => (string) $personId]
        ));
        $contactId = $email['data']['id'];

        // Show the person: both contacts should be nested.
        $show = $this->decode($this->people->show(Request::create('GET'), ['id' => (string) $personId]));
        $this->assertCount(2, $show['data']['contacts']);

        // Update the contact.
        $updated = $this->decode($this->contacts->update(
            Request::create('PUT', body: ['type' => 'whatsapp', 'value' => '+55 62 91234-5678']),
            ['id' => (string) $contactId]
        ));
        $this->assertSame('whatsapp', $updated['data']['type']);

        // Delete the contact, then confirm only one contact remains.
        $deleteStatus = $this->contacts->destroy(Request::create('DELETE'), ['id' => (string) $contactId])->status();
        $this->assertSame(204, $deleteStatus);

        $show = $this->decode($this->people->show(Request::create('GET'), ['id' => (string) $personId]));
        $this->assertCount(1, $show['data']['contacts']);

        return (int) $personId;
    }

    /** @depends testFullPersonAndContactLifecycle */
    public function testDeletingAPersonCascadesToTheirContacts(int $personId): void
    {
        $this->people->destroy(Request::create('DELETE'), ['id' => (string) $personId]);

        $this->expectException(NotFoundException::class);
        $this->people->show(Request::create('GET'), ['id' => (string) $personId]);
    }

    public function testCreatingAContactForAMissingPersonFails(): void
    {
        $this->expectException(NotFoundException::class);

        $this->contacts->store(
            Request::create('POST', body: ['type' => 'phone', 'value' => '123']),
            ['personId' => '99999']
        );
    }

    public function testUpdatingAMissingPersonFails(): void
    {
        $this->expectException(NotFoundException::class);

        $this->people->update(Request::create('PUT', body: ['name' => 'Nobody']), ['id' => '99999']);
    }

    /** @return array<string, mixed> */
    private function decode(\App\Http\Response $response): array
    {
        return json_decode($response->body(), true);
    }
}
