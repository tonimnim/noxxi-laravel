<?php

namespace App\Models;

use Laravel\Passport\Client as BaseClient;

class PassportClient extends BaseClient
{
    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'personal_access_client' => 'boolean',
        'password_client' => 'boolean',
        'revoked' => 'boolean',
        'redirect_uris' => 'array',
        'grant_types' => 'array',
    ];

    /**
     * Get the grant types for the client.
     *
     * @return array
     */
    public function getGrantTypesAttribute($value)
    {
        if (is_string($value)) {
            return json_decode($value, true) ?? [];
        }

        return $value ?? [];
    }

    /**
     * Set the grant types for the client.
     *
     * @param  array|string  $value
     * @return void
     */
    public function setGrantTypesAttribute($value)
    {
        $this->attributes['grant_types'] = is_array($value) ? json_encode($value) : $value;
    }

    /**
     * Get the redirect URIs for the client.
     *
     * @return array
     */
    public function getRedirectUrisAttribute($value)
    {
        if (is_string($value)) {
            return json_decode($value, true) ?? [];
        }

        return $value ?? [];
    }

    /**
     * Set the redirect URIs for the client.
     *
     * @param  array|string  $value
     * @return void
     */
    public function setRedirectUrisAttribute($value)
    {
        $this->attributes['redirect_uris'] = is_array($value) ? json_encode($value) : $value;
    }
}
