<?php

namespace Boy132\Subdomains\Models;

use App\Models\Server;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $name
 * @property string $record_type
 * @property int $domain_id
 * @property CloudflareDomain $domain
 * @property int $server_id
 * @property Server $server
 */
class Subdomain extends Model implements HasLabel
{
    protected $fillable = [
        'name',
        'record_type',
        'domain_id',
        'server_id',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::created(function (self $model) {
            $model->createOnCloudflare();
        });

        static::updated(function (self $model) {
            $model->updateOnCloudflare();
        });

        static::deleted(function (self $model) {
            $model->deleteOnCloudflare();
        });
    }

    public function domain(): BelongsTo
    {
        return $this->BelongsTo(CloudflareDomain::class, 'domain_id');
    }

    public function server(): BelongsTo
    {
        return $this->BelongsTo(Server::class);
    }

    public function getLabel(): string|Htmlable|null
    {
        return $this->name . '.' . $this->domain->name;
    }

    protected function createOnCloudflare(): void
    {
        // TODO
        /*$key = new \Cloudflare\API\Auth\APIKey(config('subdomains.email'), config('subdomains.key'));
        $adapter = new \Cloudflare\API\Adapter\Guzzle($key);
        $zones = new \Cloudflare\API\Endpoints\Zones($adapter);
        $dns = new \Cloudflare\API\Endpoints\DNS($adapter);

        $zoneID = $zones->getZoneID($this->name);

        $dns->addRecord($zoneID, $this->record_type, $this->name, $this->server->allocation->ip, 120, false);*/
    }

    protected function updateOnCloudflare(): void
    {
        // TODO
        /*$key = new \Cloudflare\API\Auth\APIKey(config('subdomains.email'), config('subdomains.key'));
        $adapter = new \Cloudflare\API\Adapter\Guzzle($key);
        $zones = new \Cloudflare\API\Endpoints\Zones($adapter);
        $dns = new \Cloudflare\API\Endpoints\DNS($adapter);

        $zoneID = $zones->getZoneID($this->name);

        $result = $dns->listRecords($zoneID, $this->record_type, $this->getLabel())->result;

        $dns->updateRecordDetails($zoneID, $result[0]->id, [
            'type' => $this->record_type,
            'name' => $this->name,
            'content' => $this->server->allocation->ip,
            'ttl' => 120,
            'proxied' => false,
        ]);*/
    }

    protected function deleteOnCloudflare(): void
    {
        // TODO
        /*$key = new \Cloudflare\API\Auth\APIKey(config('subdomains.email'), config('subdomains.key'));
        $adapter = new \Cloudflare\API\Adapter\Guzzle($key);
        $zones = new \Cloudflare\API\Endpoints\Zones($adapter);
        $dns = new \Cloudflare\API\Endpoints\DNS($adapter);

        $zoneID = $zones->getZoneID($this->name);

        $result = $dns->listRecords($zoneID, $this->record_type, $this->getLabel())->result;

        $dns->deleteRecord($zoneID, $result[0]->id);*/
    }
}
