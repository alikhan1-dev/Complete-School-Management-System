<?php

namespace App\Modules\Library\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Library\Services\BookService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI admin/book — list/create/edit/delete.
 * Deferred: import, getAvailQuantity, issue reports.
 */
class BookController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected BookService $books,
    ) {
    }

    public function index(): View
    {
        abort_unless($this->permissions->hasPrivilege('books', 'can_view'), 403);

        return view('shared::layouts.admin', [
            'title' => 'Books',
            'contentView' => 'library::admin.books.index',
            'books' => $this->books->listBooks(),
            'editing' => null,
            'canAdd' => $this->permissions->hasPrivilege('books', 'can_add'),
            'canEdit' => $this->permissions->hasPrivilege('books', 'can_edit'),
            'canDelete' => $this->permissions->hasPrivilege('books', 'can_delete'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('books', 'can_add'), 403);

        $this->books->create($this->validated($request));

        return redirect()
            ->route('library.books.getall')
            ->with('success', 'Book created successfully.');
    }

    public function edit(int $id): View
    {
        abort_unless($this->permissions->hasPrivilege('books', 'can_edit'), 403);

        return view('shared::layouts.admin', [
            'title' => 'Edit Book',
            'contentView' => 'library::admin.books.index',
            'books' => $this->books->listBooks(),
            'editing' => $this->books->find($id),
            'canAdd' => $this->permissions->hasPrivilege('books', 'can_add'),
            'canEdit' => true,
            'canDelete' => $this->permissions->hasPrivilege('books', 'can_delete'),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('books', 'can_edit'), 403);

        $book = $this->books->find($id);
        $this->books->update($book, $this->validated($request));

        return redirect()
            ->route('library.books.getall')
            ->with('success', 'Book updated successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('books', 'can_delete'), 403);

        $this->books->delete($this->books->find($id));

        return redirect()
            ->route('library.books.getall')
            ->with('success', 'Book deleted successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request): array
    {
        return $request->validate([
            'book_title' => ['required', 'string', 'max:100'],
            'book_no' => ['nullable', 'string', 'max:50'],
            'isbn_no' => ['nullable', 'string', 'max:100'],
            'subject' => ['nullable', 'string', 'max:100'],
            'rack_no' => ['nullable', 'string', 'max:100'],
            'publish' => ['nullable', 'string', 'max:100'],
            'author' => ['nullable', 'string', 'max:100'],
            'qty' => ['nullable', 'integer', 'min:0'],
            'perunitcost' => ['nullable', 'numeric', 'min:0'],
            'postdate' => ['nullable', 'date'],
            'description' => ['nullable', 'string'],
        ]);
    }
}
