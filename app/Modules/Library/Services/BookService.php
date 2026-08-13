<?php

namespace App\Modules\Library\Services;

use App\Modules\Library\Models\Book;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * CI admin/book — catalog CRUD + CSV import.
 * Deferred: reports, getAvailQuantity.
 */
class BookService
{
    /** @var list<string> */
    public const IMPORT_FIELDS = [
        'book_title',
        'book_no',
        'isbn_no',
        'subject',
        'rack_no',
        'publish',
        'author',
        'qty',
        'perunitcost',
        'postdate',
        'description',
        'available',
    ];

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
     * CI admin/book/exportformat — sample header row.
     */
    public function sampleCsvContent(): string
    {
        return implode(',', self::IMPORT_FIELDS)."\n";
    }

    /**
     * CI admin/book/import — parse CSV and insert_batch into books.
     *
     * @return int Number of rows imported
     */
    public function importFromCsv(string $absolutePath): int
    {
        $rows = $this->parseCsvRows($absolutePath);

        if ($rows === []) {
            throw ValidationException::withMessages([
                'file' => 'No valid book rows found in the CSV file.',
            ]);
        }

        $payloads = [];
        foreach ($rows as $row) {
            $title = trim((string) ($row['book_title'] ?? ''));
            if ($title === '') {
                continue;
            }

            $payloads[] = $this->normalizedPayload([
                'book_title' => $title,
                'book_no' => $row['book_no'] ?? '',
                'isbn_no' => $row['isbn_no'] ?? '',
                'subject' => $row['subject'] ?? null,
                'rack_no' => $row['rack_no'] ?? '',
                'publish' => $row['publish'] ?? null,
                'author' => $row['author'] ?? null,
                'qty' => $row['qty'] ?? null,
                'perunitcost' => $row['perunitcost'] ?? null,
                'postdate' => $row['postdate'] ?? null,
                'description' => $row['description'] ?? null,
                'available' => $row['available'] ?? 'yes',
                'is_active' => $row['is_active'] ?? 'no',
            ]);
        }

        if ($payloads === []) {
            throw ValidationException::withMessages([
                'file' => 'No valid book rows found in the CSV file.',
            ]);
        }

        DB::transaction(function () use ($payloads) {
            $now = now();
            foreach ($payloads as &$payload) {
                $payload['created_at'] = $now;
                $payload['updated_at'] = $now;
            }
            unset($payload);

            foreach (array_chunk($payloads, 100) as $chunk) {
                Book::query()->insert($chunk);
            }
        });

        return count($payloads);
    }

    /**
     * @return list<array<string, string>>
     */
    protected function parseCsvRows(string $absolutePath): array
    {
        $handle = fopen($absolutePath, 'rb');
        if ($handle === false) {
            throw ValidationException::withMessages([
                'file' => 'Unable to read the uploaded CSV file.',
            ]);
        }

        try {
            $firstLine = fgets($handle);
            if ($firstLine === false) {
                return [];
            }

            $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';
            rewind($handle);

            $header = fgetcsv($handle, 0, $delimiter);
            if ($header === false || $header === [null] || $header === []) {
                return [];
            }

            $header = array_map(static function ($column) {
                return strtolower(trim((string) $column));
            }, $header);

            $rows = [];
            while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
                if ($data === [null] || $data === []) {
                    continue;
                }

                // CI CSVReader quirk: sometimes the whole line is one field then re-split.
                if (count($data) === 1 && is_string($data[0]) && str_contains($data[0], ',')) {
                    $data = str_getcsv($data[0]);
                }

                if (count($data) !== count($header)) {
                    continue;
                }

                $assoc = [];
                foreach ($header as $index => $key) {
                    if ($key === '') {
                        continue;
                    }
                    $assoc[$key] = trim((string) ($data[$index] ?? ''));
                }

                if ($assoc === []) {
                    continue;
                }

                $rows[] = $assoc;
            }

            return $rows;
        } finally {
            fclose($handle);
        }
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
