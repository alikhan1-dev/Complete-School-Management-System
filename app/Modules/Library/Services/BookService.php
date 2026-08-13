<?php

namespace App\Modules\Library\Services;

use App\Modules\Library\Models\Book;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * CI admin/book — catalog CRUD.
 * Deferred: CSV import, members, issue/return, reports, getAvailQuantity.
 */
class BookService
{
    /**
     * @return Collection<int, Book>
     */
    public function listBooks(): Collection
    {
        return Book::query()->orderByDesc('id')->get();
    }

    public function find(int $id): Book
    {
        return Book::query()->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Book
    {
        return Book::query()->create($this->normalizedPayload($data));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Book $book, array $data): Book
    {
        $book->fill($this->normalizedPayload($data));
        $book->save();

        return $book;
    }

    public function delete(Book $book): void
    {
        DB::transaction(function () use ($book) {
            DB::table('book_issues')->where('book_id', $book->id)->delete();
            $book->delete();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function normalizedPayload(array $data): array
    {
        $price = $data['perunitcost'] ?? null;
        if ($price === null || $price === '') {
            $perUnitCost = null;
        } else {
            // CI convertCurrencyFormatToBaseAmount — store numeric; full currency helper deferred.
            $perUnitCost = (float) str_replace(',', '', (string) $price);
        }

        $qty = $data['qty'] ?? null;
        $qty = ($qty === null || $qty === '') ? null : (int) $qty;

        $postdate = $data['postdate'] ?? null;
        $postdate = ($postdate === null || $postdate === '') ? null : (string) $postdate;

        return [
            'book_title' => (string) $data['book_title'],
            'book_no' => (string) ($data['book_no'] ?? ''),
            'isbn_no' => (string) ($data['isbn_no'] ?? ''),
            'subject' => $data['subject'] ?? null,
            'rack_no' => (string) ($data['rack_no'] ?? ''),
            'publish' => $data['publish'] ?? null,
            'author' => $data['author'] ?? null,
            'qty' => $qty,
            'perunitcost' => $perUnitCost,
            'postdate' => $postdate,
            'description' => $data['description'] ?? null,
            'available' => (string) ($data['available'] ?? 'yes'),
            'is_active' => (string) ($data['is_active'] ?? 'no'),
        ];
    }
}
