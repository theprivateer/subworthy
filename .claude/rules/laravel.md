---
paths: "**/*.php"
---

# Laravel 11+ Backend Rules

### Laravel 11 Structure
- **bootstrap/app.php** central config (routing, middleware, exceptions)
- **Single AppServiceProvider** replaces Auth/Event/Route providers
- **No Kernel files** — configure middleware in bootstrap/app.php
- **api.php not included** — `php artisan install:api`
- **Config files removed** — `php artisan config:publish`
- `casts()` method instead of `$casts` property
- Schedule in `routes/console.php` via `Schedule` facade

```php
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(web: __DIR__.'/../routes/web.php', api: __DIR__.'/../routes/api.php', health: '/up')
    ->withMiddleware(fn(Middleware $m) => $m->api(prepend: [TokenMiddleware::class]))
    ->withExceptions(fn(Exceptions $e) => $e->shouldRenderJsonWhen(fn($r) => $r->is('api/*')))
    ->create();
```

### Architecture

**Controllers**: Thin, delegate to Services/Actions. `--invokable` for single actions.

| Services | Actions |
|----------|---------|
| Multiple methods: `UserService::create()` | Single op: `CreateUserAction::execute()` |

**Avoid Repository** — use Eloquent scopes: `scopeActive($q) { return $q->where('status', 'active'); }`

**Form Requests**: ALWAYS `$request->validated()`, never `$request->all()`

**Events vs Observers**: Events = app-level, multiple listeners. Observers = model lifecycle (sparingly).

### Eloquent Performance

**N+1 Prevention**:
```php
Model::preventLazyLoading(!app()->isProduction());
$posts = Post::with(['author:id,name', 'comments'])->get();
```

| `with()` | `load()` | `loadMissing()` |
|----------|----------|-----------------|
| Query time | After retrieval | Only if not loaded |

**Query Rules**: `->select(['id','name'])` needed columns; `->withCount('posts')` not `$user->posts->count()`; `->exists()` not `count() > 0`; `chunkById()` large datasets; `cursor()` memory-critical; Query Builder for bulk (2x faster)

**Indexing**: Foreign keys, filtered columns (`status`, `email`), composite for multi-column WHERE

### Database Transactions

**Always re-read data inside transactions** — models passed to actions may be stale.

**`refresh()` is NOT safe** — MySQL REPEATABLE READ means normal SELECTs (including `refresh()`) read from a snapshot, not the latest committed data. Only `lockForUpdate()` guarantees fresh data + mutual exclusion.

**Read-modify-write pattern:**
```php
return DB::transaction(function () use ($model, ...) {
    // Re-fetch with exclusive lock — fresh data + mutual exclusion
    $model = Model::lockForUpdate()->findOrFail($model->id);

    // Validation MUST happen after the lock (stale data could pass validation)
    if ($someCondition) {
        throw ValidationException::withMessages([...]);
    }

    // Modify and save
    $model->amount = $model->amount->add($delta);
    $model->save();
});
```

**Rules:**
- `lockForUpdate()->findOrFail()` must be the **first operation** inside the transaction
- Move all state-dependent validation **inside** the transaction, after the lock
- Never rely on `refresh()` for concurrency safety inside a transaction

**Pre-deployment**: Modify existing migrations directly and run `php artisan migrate:fresh`

**Post-deployment**: Create new migrations to alter schema (never modify deployed migrations)

### Jobs & Queues

```php
class ProcessPayment implements ShouldQueue {
    use Queueable;
    public $tries = 3, $timeout = 60;
    public function __construct(public Order $order) { $this->order = $order->withoutRelations(); }
    public function backoff(): array { return [1, 5, 30]; }
    public function failed(?Throwable $e): void { /* notify */ }
}
```

**Rules**: Pass IDs not models; `after_commit => true`; `ShouldBeUnique` prevents duplicates; always implement `failed()`

**Batch/Chain**: `Bus::batch([...])->then(fn() => ...)->dispatch()` parallel; `Bus::chain([...])->dispatch()` sequential

### Testing (Pest)

```php
pest()->extends(TestCase::class)->use(RefreshDatabase::class)->in('Feature');

test('creates order', function () {
    $this->actingAs(User::factory()->create())
        ->postJson('/api/orders', ['product_id' => 1])
        ->assertStatus(201)->assertJsonStructure(['data' => ['id', 'status']]);
});

it('rejects invalid emails', fn(string $email) =>
    $this->postJson('/register', ['email' => $email])->assertJsonValidationErrors('email')
)->with(['invalid', 'missing@', '']);
```

**Faking**: `Queue::fake(); Http::fake(['api.example.com/*' => Http::response([...])]); Mail::fake(); Storage::fake('s3'); Event::fake([OrderShipped::class]);`

**Use `RefreshDatabase`** for most tests, `DatabaseTruncation` if code uses transactions

**Sharding** (different from `--parallel`): Split tests across CI jobs for faster pipelines:
```bash
# CI job 1:              # CI job 2:              # CI job 3:
vendor/bin/pest --shard=1/3   vendor/bin/pest --shard=2/3   vendor/bin/pest --shard=3/3
```
Use sharding for large test suites in CI; use `--parallel` for local multi-core execution within a single process.

### Browser Testing (Pest Browser)

Use `pestphp/pest-plugin-browser` — NOT Laravel Dusk.

```bash
composer require pestphp/pest-plugin-browser --dev
php artisan vendor:publish --tag=pest-browser
```

```php
use function Pest\Browser\visit;

it('can log in', function () {
    visit('/')->click('Login')->type('email', 'user@example.com')
        ->type('password', 'password')->press('Sign in')
        ->assertPathIs('/dashboard')->assertSee('Welcome back');
});

it('shows validation errors', function () {
    visit('/register')->press('Create Account')->assertSee('The email field is required');
});
```

**Run**: `php artisan serve & npx playwright install chromium` (first time), `./vendor/bin/pest --group=browser`

**Base URL**: `APP_URL` in `.env` (Herd: `https://subworthy.test`)

### API Development

**Resources**: `'posts' => PostResource::collection($this->whenLoaded('posts'))`, `'posts_count' => $this->whenCounted('posts')`

**Rate Limiting**: `RateLimiter::for('api', fn(Request $r) => Limit::perMinute(60)->by($r->user()?->id ?: $r->ip()))`

**Sanctum**: SPAs use cookie auth with `statefulApi()`, mobile uses `createToken()`

**Versioning**: URL path (`/api/v1/`, `/api/v2/`)

### Email (Mailables)

**Always use Mailables** — never manually construct emails with views:

```bash
php artisan make:mail OrderShipped                           # Basic
php artisan make:mail OrderShipped --markdown=mail.orders.shipped  # Markdown template
```

```php
// ❌ Bad: Manual email construction
Mail::send('emails.order', ['order' => $order], function ($message) use ($user) {
    $message->to($user->email)->subject('Order Shipped');
});

// ✅ Good: Mailable class
Mail::to($user)->send(new OrderShipped($order));
```

**Mailable structure** (Envelope, Content, Attachments):
```php
class OrderShipped extends Mailable implements ShouldQueue
{
    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Order Shipped');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.orders.shipped');
    }

    public function attachments(): array
    {
        return [Attachment::fromPath('/path/to/invoice.pdf')];
    }
}
```

**Queueing**: Implement `ShouldQueue` to queue by default. Use `->afterCommit()` when sending after DB transactions.

**Testing**: `Mail::fake(); Mail::assertSent(OrderShipped::class, fn($m) => $m->hasTo($user->email));`

### Security

- ALWAYS `$request->validated()`, never `$request->all()`
- `$fillable` (whitelist), not empty `$guarded`
- Validate column names in dynamic queries
- Parameterized raw SQL: `DB::select('...WHERE id = ?', [$id])`
- `APP_DEBUG=false` in production

**Auth**: `$this->authorize('update', $post)` Policy; `Gate::define('admin', fn(User $u) => $u->role === 'admin')`

### Caching

`Cache::remember('active_users', 3600, fn() => User::active()->get())` | `Cache::tags(['users'])->flush()` (Redis only)

### Error Handling

```php
class PaymentFailedException extends Exception {
    public function render(Request $request): JsonResponse {
        return response()->json(['error' => 'payment_failed'], 402);
    }
}
```

**Logging**: Never log sensitive data. `Log::withContext(['request_id' => ...])` in middleware.

### Code Formatting (Pint)

```bash
./vendor/bin/pint                    # Format all
./vendor/bin/pint --dirty            # Changed files only
./vendor/bin/pint --test             # Check without fixing
```

**GitHub Actions**: Use `/setup-laravel-app-workflows` to set up Pint + Pest CI workflows.

### Production

`php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan optimize`

### URL Generation

**Always use Laravel URL helpers** — never manually concatenate `config('app.url')` with paths:

```php
// ❌ Bad
$url = config('app.url') . '/users/' . $user->id;

// ✅ Good
$url = url('/users/' . $user->id);
$url = route('users.show', $user);           // Named route (preferred)
$url = action([UserController::class, 'show'], $user);
```

**Helpers**: `url()` absolute URL, `route()` named routes, `action()` controller methods, `asset()` for assets

### Config Files

When generating or editing any file in `config/`, document every key using Laravel's standard block-comment style:

```php
/*
|--------------------------------------------------------------------------
| Section Title
|--------------------------------------------------------------------------
|
| One or two sentences explaining what this option controls and when you
| would change it. Mention the env() fallback and its default value.
|
*/

'option_key' => env('ENV_VAR', 'default'),
```

- Every top-level key must have its own `|---|` block comment above it.
- Comments explain the *purpose and effect* of the option, not just its name.
- Reference the corresponding `env()` variable and default value in the comment when one is used.
- Match the indentation and brace style of existing Laravel config files exactly.

### Common Mistakes

- Never `$request->all()` (use `validated()`), lazy load in loops (use `with()`), `count() > 0` (use `exists()`), `$user->posts->count()` (use `withCount()`), `env()` outside config (use `config()`), full models in jobs (use IDs), `config('app.url') . '/path'` (use `url()`, `route()`)
- Always index FKs, `APP_DEBUG=false` in production
- Never use `compact()` when passing data to a view, always pass an array of values
