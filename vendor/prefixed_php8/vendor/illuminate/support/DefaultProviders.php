<?php

namespace Org\Wplake\Advanced_Views\Optional_Vendors\Illuminate\Support;

class DefaultProviders
{
    /**
     * The current providers.
     *
     * @var array
     */
    protected $providers;
    /**
     * Create a new default provider collection.
     *
     * @return void
     */
    public function __construct(?array $providers = null)
    {
        $this->providers = $providers ?: [\Org\Wplake\Advanced_Views\Optional_Vendors\Illuminate\Auth\AuthServiceProvider::class, \Org\Wplake\Advanced_Views\Optional_Vendors\Illuminate\Broadcasting\BroadcastServiceProvider::class, \Org\Wplake\Advanced_Views\Optional_Vendors\Illuminate\Bus\BusServiceProvider::class, \Org\Wplake\Advanced_Views\Optional_Vendors\Illuminate\Cache\CacheServiceProvider::class, \Org\Wplake\Advanced_Views\Optional_Vendors\Illuminate\Foundation\Providers\ConsoleSupportServiceProvider::class, \Org\Wplake\Advanced_Views\Optional_Vendors\Illuminate\Cookie\CookieServiceProvider::class, \Org\Wplake\Advanced_Views\Optional_Vendors\Illuminate\Database\DatabaseServiceProvider::class, \Org\Wplake\Advanced_Views\Optional_Vendors\Illuminate\Encryption\EncryptionServiceProvider::class, \Org\Wplake\Advanced_Views\Optional_Vendors\Illuminate\Filesystem\FilesystemServiceProvider::class, \Org\Wplake\Advanced_Views\Optional_Vendors\Illuminate\Foundation\Providers\FoundationServiceProvider::class, \Org\Wplake\Advanced_Views\Optional_Vendors\Illuminate\Hashing\HashServiceProvider::class, \Org\Wplake\Advanced_Views\Optional_Vendors\Illuminate\Mail\MailServiceProvider::class, \Org\Wplake\Advanced_Views\Optional_Vendors\Illuminate\Notifications\NotificationServiceProvider::class, \Org\Wplake\Advanced_Views\Optional_Vendors\Illuminate\Pagination\PaginationServiceProvider::class, \Org\Wplake\Advanced_Views\Optional_Vendors\Illuminate\Auth\Passwords\PasswordResetServiceProvider::class, \Org\Wplake\Advanced_Views\Optional_Vendors\Illuminate\Pipeline\PipelineServiceProvider::class, \Org\Wplake\Advanced_Views\Optional_Vendors\Illuminate\Queue\QueueServiceProvider::class, \Org\Wplake\Advanced_Views\Optional_Vendors\Illuminate\Redis\RedisServiceProvider::class, \Org\Wplake\Advanced_Views\Optional_Vendors\Illuminate\Session\SessionServiceProvider::class, \Org\Wplake\Advanced_Views\Optional_Vendors\Illuminate\Translation\TranslationServiceProvider::class, \Org\Wplake\Advanced_Views\Optional_Vendors\Illuminate\Validation\ValidationServiceProvider::class, \Org\Wplake\Advanced_Views\Optional_Vendors\Illuminate\View\ViewServiceProvider::class];
    }
    /**
     * Merge the given providers into the provider collection.
     *
     * @param  array  $providers
     * @return static
     */
    public function merge(array $providers)
    {
        $this->providers = \array_merge($this->providers, $providers);
        return new static($this->providers);
    }
    /**
     * Replace the given providers with other providers.
     *
     * @param  array  $replacements
     * @return static
     */
    public function replace(array $replacements)
    {
        $current = collect($this->providers);
        foreach ($replacements as $from => $to) {
            $key = $current->search($from);
            $current = \is_int($key) ? $current->replace([$key => $to]) : $current;
        }
        return new static($current->values()->toArray());
    }
    /**
     * Disable the given providers.
     *
     * @param  array  $providers
     * @return static
     */
    public function except(array $providers)
    {
        return new static(collect($this->providers)->reject(fn($p) => \in_array($p, $providers))->values()->toArray());
    }
    /**
     * Convert the provider collection to an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->providers;
    }
}
