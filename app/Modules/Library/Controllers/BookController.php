<?php

namespace App\Modules\Library\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Library\Services\BookService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * CI admin/book — list/create/edit/delete/import.
 * Deferred: getAvailQuantity, issue reports.
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

    public function import(Request $request): View|RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('books', 'can_view'), 403);

        if ($request->isMethod('post')) {
            abort_unless($this->permissions->hasPrivilege('books', 'can_add'), 403);

            $request->validate([
                'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
            ]);

            $uploaded = $request->file('file');
            abort_unless($uploaded !== null, 422);

            $extension = strtolower($uploaded->getClientOriginalExtension());
            if ($extension !== 'csv') {
                return redirect()
                    ->route('library.books.import')
                    ->withErrors(['file' => 'Only CSV files are allowed.']);
            }

            $count = $this->books->importFromCsv($uploaded->getRealPath());

            return redirect()
                ->route('library.books.import')
                ->with('success', 'Total '.$count.' records found in CSV file. Records imported successfully.');
        }

        return view('shared::layouts.admin', [
            'title' => 'Import Book',
            'contentView' => 'library::admin.books.import',
            'fields' => BookService::IMPORT_FIELDS,
            'canAdd' => $this->permissions->hasPrivilege('books', 'can_add'),
        ]);
    }

    public function exportFormat(): StreamedResponse
    {
        abort_unless($this->permissions->hasPrivilege('books', 'can_view'), 403);

        $content = $this->books->sampleCsvContent();

        return response()->streamDownload(static function () use ($content) {
            echo $content;
        }, 'import_book_sample_file.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
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
