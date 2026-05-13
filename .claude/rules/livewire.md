---
paths: "{app/Livewire,resources/views/livewire}/**/*"
---

# Livewire 4 Rules

**Stack**: Livewire 4.x, PHP ≥8.1, Laravel ≥10. Single-file components default.

### Critical Rules
- **Authorize all actions** — `$this->authorize()` before mutations
- **Primitive props only** — no large objects/models as public properties
- **`wire:key` in loops** — always unique, prevents DOM bugs
- **Max 1 level nesting** — deep components cause diffing issues
- **`#[Locked]` for sensitive IDs** — public props visible in wire:snapshot

### Component Formats

```bash
php artisan make:livewire counter       # Single-file
php artisan make:livewire counter --mfc # Multi-file
php artisan make:livewire counter --class # Class-based (v3)
```

```php
<?php // resources/views/components/⚡counter.blade.php
use Livewire\Component;
new class extends Component {
    public int $count = 0;
    public function increment(): void { $this->count++; }
}; ?>
<div>
    <span>{{ $count }}</span>
    <button wire:click="increment">+</button>
</div>
```

### v3 → v4 Breaking Changes

| Change | v3 | v4 |
|--------|----|----|
| Config | `layout` | `component_layout` |
| Config | `lazy_placeholder` | `component_placeholder` |
| Config | `smart_wire_key: false` | `smart_wire_key: true` (default) |
| Routes (SFC) | `Route::get()` | `Route::livewire()` |
| stream() | `to: '#el'` | `el: '#el'` |
| wire:model | Responds to bubbled events | Add `.deep` for bubbled events |
| wire:scroll | `wire:scroll` | `wire:navigate:scroll` |
| wire:transition | Alpine-based, has modifiers | View Transitions API, no modifiers |
| JS hooks | `Livewire.hook('commit')` | `Livewire.interceptMessage()` |
| JS $wire.$js | `$wire.$js('name', fn)` | `$wire.$js.name = () => {}` |
| mount() | `mount()` | `mount($slots = null)` |

**Component tags must be closed** (v4 strict):
```blade
<!-- ❌ Breaks in v4 --> <livewire:counter>
<!-- ✅ Correct --> <livewire:counter /> or <livewire:counter></livewire:counter>
```

### New in v4

**@island** — Partial Hydration:
```blade
@island(name: 'stats', lazy: true, poll: '5s')
    @placeholder <div class="animate-pulse h-32 bg-gray-200"></div> @endplaceholder
    <div>{{ $this->expensiveQuery }}</div>
@endisland
<button wire:island="stats" wire:click="$refresh">Refresh</button>
```

**wire:intersect**: `wire:intersect="loadMore"` | `.once` | `:leave` | `.half`

**wire:sort**:
```blade
<ul wire:sort="reorder">
    @foreach($items as $item)
        <li wire:sort:item="{{ $item->id }}" wire:key="{{ $item->id }}">{{ $item->name }}</li>
    @endforeach
</ul>
```

**wire:ref**: `<div wire:ref="comment-{{ $id }}">` → `$refs['comment-123'].scrollIntoView()`

**Action Modifiers**: `.renderless` (no re-render), `.preserve-scroll`, `.async` (non-blocking)

**Deferred & Bundled Loading**:
```blade
<livewire:heavy-component defer />  <!-- Load immediately after page load -->
<livewire:chart lazy />             <!-- Load when visible -->
<livewire:widget lazy.bundle />     <!-- Bundle with other lazy components -->
```

**Auto data-loading**: `class="data-loading:opacity-50 data-loading:pointer-events-none"`

**JS Enhancements**:
```javascript
$wire.$errors           // Access error bag from JS
$wire.$intercept()      // Modify outgoing requests
```

### PHP Attributes

| Attribute | Usage |
|-----------|-------|
| `#[Validate('required\|min:5')]` | Validation rules |
| `#[Computed]` / `#[Computed(persist: true, seconds: 3600)]` | Cached getter |
| `#[Locked]` | Prevent frontend modification |
| `#[Url(as: 'q', history: true)]` | Sync with query string |
| `#[On('event-name')]` | Event listener |
| `#[Reactive]` | Auto-update from parent |
| `#[Modelable]` | Enable `wire:model` on component |
| `#[Lazy]` / `#[Lazy(bundle: true)]` | Load when visible |
| `#[Renderless]` | Skip re-render after action |
| `#[Layout('layouts::admin')]` / `#[Title('Page')]` | Page config |

### Performance

**v4 Improvements** (automatic):
- Non-blocking polling — polls don't block other requests
- Parallel `wire:model.live` — live updates run concurrently
- Consolidated array updates — single message vs granular index changes

```php
// ❌ Model as property
public User $user;

// ✅ Primitives + computed
public int $userId;
#[Computed] public function user() { return User::find($this->userId); }
```

```blade
<input wire:model="name">  <!-- deferred by default -->
<input wire:model.live.debounce.300ms="search">  <!-- live + debounce -->
<livewire:chart lazy />  <!-- lazy load -->
```

```php
public function updatedSearch(): void { $this->resetPage(); }  // reset on filter
return view('users', ['users' => User::cursorPaginate(25)]);  // cursor pagination
```

### Form Object Pattern

```php
class PostForm extends Livewire\Form {
    #[Validate('required|min:5')] public string $title = '';
    #[Validate('required')] public string $content = '';
    public function store(): Post { return Post::create($this->all()); }
}

// Component
public PostForm $form;
public function save(): void {
    $this->form->validate();
    $this->redirect(route('posts.show', $this->form->store()));
}
```

### Sortable Table Pattern

```php
use Livewire\WithPagination, Livewire\Attributes\Url;

class UsersTable extends Component {
    use WithPagination;
    #[Url] public string $search = '', $sortField = 'name', $sortDir = 'asc';

    public function sortBy(string $field): void {
        $this->sortDir = ($this->sortField === $field && $this->sortDir === 'asc') ? 'desc' : 'asc';
        $this->sortField = $field;
    }
    public function updatedSearch(): void { $this->resetPage(); }
}
```

### Infinite Scroll

```php
public int $perPage = 10;
public function loadMore(): void { $this->perPage += 10; }
#[Computed] public function posts() { return Post::latest()->take($this->perPage)->get(); }
```
```blade
@foreach($this->posts as $post)
    <div wire:key="{{ $post->id }}">{{ $post->title }}</div>
@endforeach
<div wire:intersect="loadMore" wire:loading.class="opacity-50">Load more</div>
```

### File Upload

```php
use Livewire\WithFileUploads;

class Upload extends Component {
    use WithFileUploads;
    #[Validate('image|max:2048')] public $photo;
    public function save(): void { $this->validate(); $this->photo->store('photos', 'public'); }
}
```
```blade
<div x-data="{ progress: 0 }" x-on:livewire-upload-progress="progress = $event.detail.progress">
    <input type="file" wire:model="photo">
    <progress :value="progress" max="100" x-show="progress > 0"></progress>
    @if($photo) <img src="{{ $photo->temporaryUrl() }}"> @endif
</div>
```

### Bulk Actions

```php
public array $selected = [];
public bool $selectAll = false;

public function updatedSelectAll(bool $v): void {
    $this->selected = $v ? $this->users->pluck('id')->toArray() : [];
}

public function deleteSelected(): void {
    $this->authorize('delete', User::class);
    User::whereIn('id', $this->selected)->delete();
    $this->reset('selected', 'selectAll');
}
```

### Security

```php
public function delete(int $id): void {
    $post = Post::findOrFail($id);
    $this->authorize('delete', $post);
    $post->delete();
}

#[Locked] public int $postId;  // Lock sensitive props
public Post $post;  // Or model binding (identity protected)
```

### Testing

```php
Livewire::test(CreatePost::class)
    ->set('form.title', 'Test')->set('form.content', 'Body')
    ->call('save')->assertHasNoErrors()->assertRedirect('/posts');

// Validation
Livewire::test(CreatePost::class)->set('form.title', '')->call('save')
    ->assertHasErrors(['form.title' => 'required']);

// Events: ->assertDispatched('post-created')
// File: Livewire::test(Upload::class)->set('photo', UploadedFile::fake()->image('photo.jpg'))->call('save')
```

### Loading States

```blade
<div wire:loading>Loading...</div>
<div wire:loading.remove>Ready</div>
<span wire:loading wire:target="save">Saving...</span>
<button wire:loading.attr="disabled" wire:loading.class="opacity-50">Save</button>
<!-- v4: --> <button wire:click="save" class="data-loading:opacity-50">Save</button>
```

### Directives Quick Reference

| Directive | Purpose |
|-----------|---------|
| `wire:model` / `.live` / `.blur` | Data binding |
| `wire:click` / `wire:submit` | Actions |
| `wire:loading` / `.remove` / `.delay` | Loading UI |
| `wire:target="method"` | Scope loading |
| `wire:key` | Unique loop keys |
| `wire:ignore` | Skip DOM diffing |
| `wire:navigate` | SPA navigation |
| `wire:poll.5s` | Periodic refresh |
| `wire:confirm="Sure?"` | Confirmation |
| `wire:intersect` / `wire:sort` / `wire:ref` / `@island` | v4 features |

### Volt → Livewire 4 Migration

Volt components use identical syntax to v4 single-file components:
```php
// Replace: use Livewire\Volt\Component;
// With:    use Livewire\Component;
// Remove Volt from bootstrap/providers.php, uninstall package
```

### Common Mistakes

| Don't | Do |
|-------|-----|
| Models as public props | Primitives + `#[Computed]` |
| `wire:model.live` everywhere | Default deferred or `.debounce` |
| Deep nesting (2+ levels) | Max 1 level, Blade components |
| Missing `wire:key` | Always unique keys |
| Unprotected actions | `$this->authorize()` |
| Sensitive IDs exposed | `#[Locked]` or model binding |
| Unclosed component tags | Always `<x />` or `<x></x>` |
| `wire:model` expecting bubbled events | Add `.deep` modifier |
