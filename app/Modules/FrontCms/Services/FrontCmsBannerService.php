<?php

namespace App\Modules\FrontCms\Services;

use App\Modules\FrontCms\Models\CmsMediaGallery;
use App\Modules\FrontCms\Models\CmsProgram;
use App\Modules\FrontCms\Models\CmsProgramPhoto;
use Illuminate\Support\Facades\DB;

/**
 * CI cms_program_model::banner / bannerDelete + admin/front/Banner persist.
 * Media manager picker deferred with the media slice.
 */
class FrontCmsBannerService
{
    public const TYPE = 'banner';

    /**
     * @return list<array<string, mixed>>
     */
    public function listImages(): array
    {
        $program = $this->bannerProgram();
        if ($program === null) {
            return [];
        }

        return CmsMediaGallery::query()
            ->join('front_cms_program_photos', 'front_cms_program_photos.media_gallery_id', '=', 'front_cms_media_gallery.id')
            ->where('front_cms_program_photos.program_id', $program->id)
            ->select('front_cms_media_gallery.*')
            ->orderBy('front_cms_program_photos.id')
            ->get()
            ->map(fn ($row) => $row->toArray())
            ->all();
    }

    public function add(int $mediaGalleryId): bool
    {
        return DB::transaction(function () use ($mediaGalleryId) {
            $program = $this->bannerProgram();
            if ($program === null) {
                $program = CmsProgram::query()->create([
                    'type' => self::TYPE,
                    'title' => 'Banner Images',
                    'meta_title' => '',
                    'meta_description' => '',
                    'meta_keyword' => '',
                    'feature_image' => '',
                ]);
            }

            CmsProgramPhoto::query()->create([
                'program_id' => (int) $program->id,
                'media_gallery_id' => $mediaGalleryId,
            ]);

            return true;
        });
    }

    public function remove(int $mediaGalleryId): bool
    {
        return DB::transaction(function () use ($mediaGalleryId) {
            $program = $this->bannerProgram();
            if ($program === null) {
                return true;
            }

            CmsProgramPhoto::query()
                ->where('program_id', $program->id)
                ->where('media_gallery_id', $mediaGalleryId)
                ->delete();

            return true;
        });
    }

    protected function bannerProgram(): ?CmsProgram
    {
        return CmsProgram::query()
            ->where('type', self::TYPE)
            ->orderByDesc('created_at')
            ->first();
    }
}
