<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Chef extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_REVIEW = 'review';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_INACTIVE = 'inactive';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_REVIEW,
        self::STATUS_PUBLISHED,
        self::STATUS_INACTIVE,
    ];

    protected $fillable = [
        'display_name',
        'slug',
        'professional_title',
        'organisation_name',
        'city',
        'country',
        'years_experience',
        'short_intro',
        'biography',
        'cooking_philosophy',
        'quote',
        'specialties',
        'signature_dishes',
        'signature_dish_name',
        'signature_dish_image_path',
        'culinary_training',
        'career_highlights',
        'awards',
        'qa',
        'portrait_image_path',
        'hero_image_path',
        'gallery_image_paths',
        'photographer_credit',
        'website_url',
        'instagram_url',
        'linkedin_url',
        'legal_name',
        'contact_email',
        'contact_phone',
        'contact_person_name',
        'preferred_contact_method',
        'best_contact_time',
        'internal_notes',
        'image_rights_confirmed',
        'profile_use_approved',
        'restaurant_mention_approved',
        'recipe_use_approved',
        'social_promotion_approved',
        'content_approved_at',
        'approval_notes',
        'status',
        'is_featured',
        'homepage_feature_slot',
        'featured_recipe_id',
        'sort_order',
        'published_at',
        'created_by_id',
        'updated_by_id',
    ];

    protected function casts(): array
    {
        return [
            'years_experience' => 'integer',
            'specialties' => 'array',
            'qa' => 'array',
            'gallery_image_paths' => 'array',
            'image_rights_confirmed' => 'boolean',
            'profile_use_approved' => 'boolean',
            'restaurant_mention_approved' => 'boolean',
            'recipe_use_approved' => 'boolean',
            'social_promotion_approved' => 'boolean',
            'content_approved_at' => 'datetime',
            'is_featured' => 'boolean',
            'homepage_feature_slot' => 'integer',
            'sort_order' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Legacy recipe relationships are retained for data compatibility only.
     * The simplified Chef experience does not query or render them.
     */
    public function recipes(): BelongsToMany
    {
        return $this->belongsToMany(Recipe::class, 'chef_recipe')
            ->withPivot(['is_featured', 'sort_order'])
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    public function featuredRecipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class, 'featured_recipe_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', self::STATUS_PUBLISHED)
            ->where(function (Builder $published): void {
                $published
                    ->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
    }

    public function scopeHomepageFeatured(Builder $query): Builder
    {
        return $query
            ->published()
            ->where('is_featured', true)
            ->where('homepage_feature_slot', 1);
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED
            && ($this->published_at === null || $this->published_at->lessThanOrEqualTo(now()));
    }

    public function isHomepageFeaturedSelection(): bool
    {
        return $this->is_featured === true && $this->homepage_feature_slot === 1;
    }

    public function portraitUrl(): ?string
    {
        return $this->mediaUrl($this->portrait_image_path);
    }

    public function heroImageUrl(): ?string
    {
        return $this->mediaUrl($this->hero_image_path ?: $this->portrait_image_path);
    }

    public function workingImageUrl(): ?string
    {
        return $this->mediaUrl($this->hero_image_path);
    }

    public function signatureDishImageUrl(): ?string
    {
        return $this->mediaUrl($this->signature_dish_image_path);
    }

    /**
     * @return list<string>
     */
    public function galleryImageUrls(): array
    {
        return collect($this->gallery_image_paths ?? [])
            ->filter(fn (mixed $path): bool => is_string($path) && $path !== '')
            ->take(4)
            ->map(fn (string $path): ?string => $this->mediaUrl($path))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Kept for compatibility with data entered before the simplified profile.
     * The public Chef pages no longer render specialty labels.
     *
     * @return list<string>
     */
    public function specialtyList(): array
    {
        return collect($this->specialties ?? [])
            ->filter(fn (mixed $specialty): bool => is_string($specialty) && trim($specialty) !== '')
            ->map(fn (string $specialty): string => trim($specialty))
            ->unique(fn (string $specialty): string => mb_strtolower($specialty))
            ->values()
            ->all();
    }

    public function publicOrganisationLine(): ?string
    {
        if (! $this->restaurant_mention_approved || blank($this->organisation_name)) {
            return null;
        }

        return $this->organisation_name;
    }

    private function mediaUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/')) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }
}
