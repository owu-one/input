<?php

namespace App\Models;

use App\Enums\FormBlockType;
use App\Http\Resources\PublicFormBlockResource;
use App\Http\Resources\PublicFormResource;
use App\Models\Traits\TemplateExportsAndImports;
use Hashids\Hashids;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Ramsey\Uuid\Uuid;

class Form extends BaseModel
{
    use HasFactory;
    use SoftDeletes;
    use TemplateExportsAndImports;

    public const DEFAULT_BRAND_COLOR = '#1f2937';

    protected $guarded = [];

    protected $casts = [
        'is_notification_via_mail' => 'boolean',
        'show_cta_link' => 'boolean',
        'use_brighter_inputs' => 'boolean',
        'show_form_progress' => 'boolean',
        'cta_append_params' => 'boolean',
        'cta_append_session_id' => 'boolean',
        'use_cta_redirect' => 'boolean',
        'show_social_links' => 'boolean',
        'show_privacy_link' => 'boolean',
        'has_data_privacy' => 'boolean',
        'is_auto_delete_enabled' => 'boolean',
        'user_id' => 'integer',
        'published_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $hidden = [
        'user_id',
        'deleted_at',
        'avatar_path',
        'background_path',
        'user',
    ];

    protected $appends = [
        'avatar',
        'background',
        'contrast_color',
        'company_name',
        'company_description',
        'active_privacy_link',
        'active_legal_notice_link',
        'privacy_contact_person',
        'privacy_contact_email',
        'total_sessions',
        'completed_sessions',
        'completion_rate',
        'is_published',
        'is_trashed',
        'initials',
    ];

    public const TEMPLATE_ATTRIBUTES = [
        'description',
        'language',
        'avatar_path',
        'background_path',
        'brand_color',
        'text_color',
        'background_color',
        'eoc_text',
        'eoc_headline',
        'data_retention_days',
        'is_auto_delete_enabled',
        'legal_notice_link',
        'privacy_link',
        'cta_label',
        'cta_link',
        'cta_append_params',
        'cta_redirect_delay',
        'use_cta_redirect',
        'cta_append_session_id',
        'linkedin',
        'github',
        'instagram',
        'facebook',
        'twitter',
        'show_cta_link',
        'show_social_links',
        'use_brighter_inputs',
        'show_form_progress',
    ];

    protected static function boot()
    {
        parent::boot();

        self::creating(function ($model) {
            $model->uuid = (string) Uuid::uuid4();
        });

        self::created(function ($model) {
            $model->update([
                'uuid' => (new Hashids())->encode($model->id),
            ]);
        });
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }

    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at')->whereDate('published_at', '<=', Carbon::now());
    }

    public function formWebhooks(): HasMany
    {
        return $this->hasMany(FormWebhook::class);
    }

    public function formBlocks(): HasMany
    {
        return $this->hasMany(FormBlock::class);
    }

    public function formSessions(): HasMany
    {
        return $this->hasMany(FormSession::class);
    }

    public function formSessionResponses(): HasManyThrough
    {
        return $this->hasManyThrough(FormSessionResponse::class, FormBlock::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function scopeWithFilter(Builder $builder, ?string $filter)
    {
        return match ($filter) {
            'published' => $builder->published(),
            'unpublished' => $builder->whereNull('published_at')->orWhereDate('published_at', '>', Carbon::now()),
            'trashed' => $builder->withTrashed()->whereNotNull('deleted_at'),
            default => $builder,
        };
    }

    public function route()
    {
        return route('forms.show', $this->uuid);
    }

    public function isEmpty()
    {
        return $this->blocksCount() <= 0;
    }

    public function blocksCount()
    {
        return $this->formBlocks->count();
    }

    public function actionBlocksCount()
    {
        return $this->formBlocks->filter(function ($item) {
            return $item->hasResponseAction();
        })->count();
    }

    public function hasImage($type)
    {
        $fieldname = $type.'_path';

        if (! $this->$fieldname) {
            return false;
        }

        return Storage::exists($this->$fieldname);
    }

    public function getAvatarAttribute()
    {
        if ($this->hasImage('avatar')) {
            return asset('images/'.$this->avatar_path);
        }

        return false;
    }

    public function getBackgroundAttribute()
    {
        if ($this->hasImage('background')) {
            return asset('images/'.$this->background_path);
        }

        return false;
    }

    public function getIsTrashedAttribute()
    {
        return $this->deleted_at && $this->deleted_at->isPast();
    }

    public function getIsPublishedAttribute()
    {
        return $this->published_at && $this->published_at->isPast();
    }

    public function getCompanyNameAttribute()
    {
        return $this->user->company_name;
    }

    public function getCompanyDescriptionAttribute()
    {
        return $this->user->company_description;
    }

    public function getActivePrivacyLinkAttribute()
    {
        return $this->privacy_link ? $this->privacy_link : $this->user->privacy_link;
    }

    public function getActiveLegalNoticeLinkAttribute()
    {
        return $this->legal_notice_link ? $this->legal_notice_link : $this->user->legal_notice_link;
    }

    public function getPrivacyContactPersonAttribute()
    {
        return $this->user->privacy_contact_person;
    }

    public function getPrivacyContactEmailAttribute()
    {
        return $this->user->privacy_contact_email;
    }

    public function brandColor()
    {
        return $this->brand_color ? $this->brand_color : '#000000';
    }

    public function getContrastColorAttribute()
    {
        return getContrastYIQ($this->brandColor());
    }

    public function getInitialsAttribute()
    {
        $strings = mb_split(' ', $this->name);

        return implode(
            ' ',
            collect($strings)
                ->take(2)
                ->map(fn ($item) => mb_substr($item, 0, 2))
                ->toArray()
        );
    }

    public function countSessions()
    {
        $blocks = $this->formBlocks->pluck('id')->toArray();

        return FormSessionResponse::select('session')
            ->whereIn('form_block_id', $blocks)
            ->groupBy('session')
            ->get()
            ->count();
    }

    public function getTotalSessionsAttribute()
    {
        return $this->formSessions()
            ->count();
    }

    public function getCompletedSessionsAttribute()
    {
        return $this->formSessions()
            ->whereHas('formSessionResponses')
            ->get()
            ->where('is_completed', true)
            ->count();
    }

    public function getCompletionRateAttribute()
    {
        try {
            return round(($this->completedSessions / $this->totalSessions) * 100, 2);
        } catch (\Throwable $th) {
            return 0;
        }
    }

    public function isOwner(User $user = null)
    {
        return $user ? $user->id === $this->user_id : false;
    }

    public function countSessionsForCurrentMonth()
    {
        $blocks = $this->formBlocks->pluck('id')->toArray();

        return FormSessionResponse::select('*')
            ->whereYear('created_at', '=', Carbon::now())
            ->whereMonth('created_at', '=', Carbon::now())
            ->whereIn('form_block_id', $blocks)
            ->groupBy('session')
            ->get()
            ->count();
    }

    public function getJavascriptConfig()
    {
        $settings = json_encode(PublicFormResource::make($this)->resolve());

        $output = 'window.iptSettings = window.iptSettings || [];';
        $output .= "window.iptSettings = {$settings}";

        return $output;
    }

    public function getPublicStoryboard()
    {
        // Filter out blocks that are groups and have no children
        $blocks = $this->formBlocks->filter(function ($item) {
            if ($item->type === FormBlockType::group) {
                return $this->formBlocks->first(function ($child) use ($item) {
                    return $child->parent_block === $item->uuid;
                });
            }

            return true;
        })->reject(function ($item) {
            return $item->is_disabled;
        });

        $blocks = $blocks->reject(function ($item) use ($blocks) {
            // Reject all items that are children of disabled groups
            if ($item->parent_block) {
                // just check if the parent is still in the collection
                return ! $blocks->firstWhere('uuid', $item->parent_block);
            }

            return false;
        });

        $blockCount = $blocks->count();

        return [
            'count' => $blockCount,
            'blocks' => PublicFormBlockResource::collection($blocks),
        ];
    }

    /**
     * For duplicating the form we do not use the Eloquent replicate method.
     * Since we also might want to duplicated form blocks and form blocks interactions,
     * we can use the template export and import functionality.
     */
    public function duplicate(string $newName): Form
    {
        $newForm = Form::create([
            'name' => $newName,
            'user_id' => $this->user_id,
            'team_id' => $this->team_id,
        ]);

        $newForm->applyTemplate($this->toTemplate());

        return $newForm;
    }
}
