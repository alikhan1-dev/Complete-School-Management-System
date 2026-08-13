<?php

namespace App\Modules\Hostel\Services;

use App\Modules\Hostel\Models\Hostel;
use App\Modules\Hostel\Models\HostelRoom;
use App\Modules\Hostel\Models\RoomType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * CI admin/hostelroom — hostel room CRUD.
 */
class HostelRoomService
{
    /**
     * @return Collection<int, object>
     */
    public function listRooms(): Collection
    {
        return DB::table('hostel_rooms')
            ->join('hostel', 'hostel.id', '=', 'hostel_rooms.hostel_id')
            ->join('room_types', 'room_types.id', '=', 'hostel_rooms.room_type_id')
            ->orderByDesc('hostel_rooms.id')
            ->select([
                'hostel_rooms.*',
                'hostel.hostel_name',
                'room_types.room_type',
            ])
            ->get();
    }

    public function find(int $id): HostelRoom
    {
        return HostelRoom::query()->findOrFail($id);
    }

    /**
     * @return Collection<int, Hostel>
     */
    public function hostelsForSelect(): Collection
    {
        return Hostel::query()->orderBy('hostel_name')->get();
    }

    /**
     * @return Collection<int, RoomType>
     */
    public function roomTypesForSelect(): Collection
    {
        return RoomType::query()->orderBy('room_type')->get();
    }

    /**
     * CI hostelroom/getRoom — rooms by hostel.
     *
     * @return Collection<int, object>
     */
    public function roomsByHostel(int $hostelId): Collection
    {
        return DB::table('hostel_rooms')
            ->where('hostel_id', $hostelId)
            ->orderBy('room_no')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): HostelRoom
    {
        $this->assertRelatedExist($data);

        return HostelRoom::query()->create($this->normalizedPayload($data));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(HostelRoom $room, array $data): HostelRoom
    {
        $this->assertRelatedExist($data);
        $room->fill($this->normalizedPayload($data));
        $room->save();

        return $room;
    }

    public function delete(HostelRoom $room): void
    {
        DB::transaction(function () use ($room) {
            DB::table('students')->where('hostel_room_id', $room->id)->update(['hostel_room_id' => 0]);
            DB::table('student_session')->where('hostel_room_id', $room->id)->update(['hostel_room_id' => 0]);
            $room->delete();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function assertRelatedExist(array $data): void
    {
        if (! Hostel::query()->whereKey((int) $data['hostel_id'])->exists()) {
            throw ValidationException::withMessages([
                'hostel_id' => 'Selected hostel is invalid.',
            ]);
        }

        if (! RoomType::query()->whereKey((int) $data['room_type_id'])->exists()) {
            throw ValidationException::withMessages([
                'room_type_id' => 'Selected room type is invalid.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{hostel_id:int,room_type_id:int,room_no:string,no_of_bed:int|string,cost_per_bed:float|string,title:string,description:string}
     */
    protected function normalizedPayload(array $data): array
    {
        return [
            'hostel_id' => (int) $data['hostel_id'],
            'room_type_id' => (int) $data['room_type_id'],
            'room_no' => (string) $data['room_no'],
            'no_of_bed' => $data['no_of_bed'],
            'cost_per_bed' => $data['cost_per_bed'],
            'title' => (string) ($data['title'] ?? ''),
            'description' => (string) ($data['description'] ?? ''),
        ];
    }
}
