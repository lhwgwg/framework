<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;

/**
 * @group integration
 */
class EloquentModelEncryptedCastingTest extends DatabaseTestCase
{
    protected $encrypter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->encrypter = $this->mock(Encrypter::class);
        Crypt::swap($this->encrypter);

        Schema::create('encrypted_casts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('secret', 1000)->nullable();
            $table->text('secret_array')->nullable();
            $table->text('secret_json')->nullable();
            $table->text('secret_object')->nullable();
            $table->text('secret_collection')->nullable();
        });
    }

    public function testStringsAreCastable()
    {
        $this->encrypter->expects('encryptString')
            ->with('this is a secret string')
            ->andReturn('encrypted-secret-string');
        $this->encrypter->expects('decryptString')
            ->with('encrypted-secret-string')
            ->andReturn('this is a secret string');

        /** @var \Illuminate\Tests\Integration\Database\EncryptedCast $subject */
        $subject = EncryptedCast::create([
            'secret' => 'this is a secret string',
        ]);

        $this->assertSame('this is a secret string', $subject->secret);
        $this->assertDatabaseHas('encrypted_casts', [
            'id' => $subject->id,
            'secret' => 'encrypted-secret-string',
        ]);
    }

    public function testArraysAreCastable()
    {
        $this->encrypter->expects('encryptString')
            ->with('{"key1":"value1"}')
            ->andReturn('encrypted-secret-array-string');
        $this->encrypter->expects('decryptString')
            ->with('encrypted-secret-array-string')
            ->andReturn('{"key1":"value1"}');

        /** @var \Illuminate\Tests\Integration\Database\EncryptedCast $subject */
        $subject = EncryptedCast::create([
            'secret_array' => ['key1' => 'value1'],
        ]);

        $this->assertSame(['key1' => 'value1'], $subject->secret_array);
        $this->assertDatabaseHas('encrypted_casts', [
            'id' => $subject->id,
            'secret_array' => 'encrypted-secret-array-string',
        ]);
    }

    public function testJsonIsCastable()
    {
        $this->encrypter->expects('encryptString')
            ->with('{"key1":"value1"}')
            ->andReturn('encrypted-secret-json-string');
        $this->encrypter->expects('decryptString')
            ->with('encrypted-secret-json-string')
            ->andReturn('{"key1":"value1"}');

        /** @var \Illuminate\Tests\Integration\Database\EncryptedCast $subject */
        $subject = EncryptedCast::create([
            'secret_json' => ['key1' => 'value1'],
        ]);

        $this->assertSame(['key1' => 'value1'], $subject->secret_json);
        $this->assertDatabaseHas('encrypted_casts', [
            'id' => $subject->id,
            'secret_json' => 'encrypted-secret-json-string',
        ]);
    }

    public function testObjectIsCastable()
    {
        $object = new \stdClass();
        $object->key1 = 'value1';

        $this->encrypter->expects('encryptString')
            ->with('{"key1":"value1"}')
            ->andReturn('encrypted-secret-object-string');
        $this->encrypter->expects('decryptString')
            ->twice()
            ->with('encrypted-secret-object-string')
            ->andReturn('{"key1":"value1"}');

        /** @var \Illuminate\Tests\Integration\Database\EncryptedCast $object */
        $object = EncryptedCast::create([
            'secret_object' => $object,
        ]);

        $this->assertInstanceOf(\stdClass::class, $object->secret_object);
        $this->assertSame('value1', $object->secret_object->key1);
        $this->assertDatabaseHas('encrypted_casts', [
            'id' => $object->id,
            'secret_object' => 'encrypted-secret-object-string',
        ]);
    }

    public function testCollectionIsCastable()
    {
        $this->encrypter->expects('encryptString')
            ->with('{"key1":"value1"}')
            ->andReturn('encrypted-secret-collection-string');
        $this->encrypter->expects('decryptString')
            ->twice()
            ->with('encrypted-secret-collection-string')
            ->andReturn('{"key1":"value1"}');

        /** @var \Illuminate\Tests\Integration\Database\EncryptedCast $subject */
        $subject = EncryptedCast::create([
            'secret_collection' => new Collection(['key1' => 'value1']),
        ]);

        $this->assertInstanceOf(Collection::class, $subject->secret_collection);
        $this->assertSame('value1', $subject->secret_collection->get('key1'));
        $this->assertDatabaseHas('encrypted_casts', [
            'id' => $subject->id,
            'secret_collection' => 'encrypted-secret-collection-string',
        ]);
    }
}

/**
 * @property $secret
 * @property $secret_array
 * @property $secret_json
 * @property $secret_object
 * @property $secret_collection
 */
class EncryptedCast extends Model
{
    public $timestamps = false;
    protected $guarded = [];

    public $casts = [
        'secret' => 'encrypted',
        'secret_array' => 'encrypted:array',
        'secret_json' => 'encrypted:json',
        'secret_object' => 'encrypted:object',
        'secret_collection' => 'encrypted:collection',
    ];
}
