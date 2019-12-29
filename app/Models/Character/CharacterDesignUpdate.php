<?php

namespace App\Models\Character;

use Config;
use DB;
use App\Models\Model;
use App\Models\Currency\Currency;
use App\Models\Feature\FeatureCategory;
use Illuminate\Database\Eloquent\SoftDeletes;

class CharacterDesignupdate extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'character_id', 'status', 'user_id', 'staff_id',
        'comments', 'staff_comments', 'data', 'extension',
        'use_cropper', 'x0', 'x1', 'y0', 'y1',
        'hash', 'species_id', 'rarity_id', 
        'has_comments', 'has_image', 'has_addons', 'has_features',
        'submitted_at'
    ];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'design_updates';

    /**
     * Whether the model contains timestamps to be saved and updated.
     *
     * @var string
     */
    public $timestamps = true;

    /**
     * Dates on the model to convert to Carbon instances.
     *
     * @var array
     */
    public $dates = ['submitted_at'];
    
    /**
     * Validation rules for uploaded images.
     *
     * @var array
     */
    public static $imageRules = [
        'image' => 'nullable|mimes:jpeg,gif,png',
        'thumbnail' => 'nullable|mimes:jpeg,gif,png',
    ];

    /**********************************************************************************************
    
        RELATIONS

    **********************************************************************************************/
    
    /**
     * Get the character associated with the design update.
     */
    public function character() 
    {
        return $this->belongsTo('App\Models\Character\Character', 'character_id');
    }
    
    /**
     * Get the user who created the design update.
     */
    public function user() 
    {
        return $this->belongsTo('App\Models\User\User', 'user_id');
    }

    /**
     * Get the staff who processed the design update.
     */
    public function staff() 
    {
        return $this->belongsTo('App\Models\User\User', 'staff_id');
    }

    /**
     * Get the species of the design update.
     */
    public function species() 
    {
        return $this->belongsTo('App\Models\Species', 'species_id');
    }

    /**
     * Get the rarity of the design update.
     */
    public function rarity() 
    {
        return $this->belongsTo('App\Models\Rarity', 'rarity_id');
    }

    /**
     * Get the features (traits) attached to the design update, ordered by display order.
     */
    public function features() 
    {
        $ids = FeatureCategory::orderBy('sort', 'DESC')->pluck('id')->toArray();

        return $this->hasMany('App\Models\Character\CharacterFeature', 'character_image_id')->where('character_features.character_type', 'Update')->join('features', 'features.id', '=', 'character_features.feature_id')->orderByRaw(DB::raw('FIELD(features.feature_category_id, '.implode(',', $ids).')'));
    }

    /**
     * Get the features (traits) attached to the design update with no extra sorting.
     */
    public function rawFeatures() 
    {
        return $this->hasMany('App\Models\Character\CharacterFeature', 'character_image_id')->where('character_features.character_type', 'Update');
    }
    
    /**
     * Get the designers attached to the design update.
     */
    public function designers() 
    {
        return $this->hasMany('App\Models\Character\CharacterImageCreator', 'character_image_id')->where('type', 'Designer')->where('character_type', 'Update');
    }
    
    /**
     * Get the artists attached to the design update.
     */
    public function artists() 
    {
        return $this->hasMany('App\Models\Character\CharacterImageCreator', 'character_image_id')->where('type', 'Artist')->where('character_type', 'Update');
    }

    /**********************************************************************************************
    
        SCOPES

    **********************************************************************************************/

    /**
     * Scope a query to only include active (Open or Pending) update requests.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', '!=', 'Approved')->where('status', '!=', 'Rejected');
    }

    /**
     * Scope a query to only include MYO slot approval requests.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeMyos($query)
    {
        $query->select('design_updates.*')->join('characters', 'design_updates.character_id', 'characters.id')->where('characters.is_myo_slot', 1);
    }

    /**
     * Scope a query to only include character design update requests.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCharacters($query)
    {
        $query->select('design_updates.*')->join('characters', 'design_updates.character_id', 'characters.id')->where('characters.is_myo_slot', 0);
    }

    /**********************************************************************************************
    
        ACCESSORS

    **********************************************************************************************/

    /**
     * Get the data attribute as an associative array.
     *
     * @return array
     */
    public function getDataAttribute()
    {
        return json_decode($this->attributes['data'], true);
    }

    /**
     * Get the items (UserItem IDs) attached to this update request.
     *
     * @return array
     */
    public function getInventoryAttribute()
    {
        // This is for showing the addons page
        // just need to retrieve a list of stack IDs to tell which ones to check

        return $this->data ? $this->data['stacks'] : [];
    }

    /**
     * Get the user-owned currencies attached to this update request.
     *
     * @return array
     */
    public function getUserBankAttribute()
    {
        return $this->data && isset($this->data['user']['currencies']) ? $this->data['user']['currencies'] : [];
    }

    /**
     * Get the character-owned currencies attached to this update request.
     *
     * @return array
     */
    public function getCharacterBankAttribute()
    {
        return $this->data && isset($this->data['character']['currencies']) ? $this->data['character']['currencies'] : [];
    }

    /**
     * Check if all sections of the form have been touched.
     *
     * @return bool
     */
    public function getIsCompleteAttribute()
    {
        return ($this->has_comments && $this->has_image && $this->has_addons && $this->has_features);
    }

    /**
     * Gets the file directory containing the model's image.
     *
     * @return string
     */
    public function getImageDirectoryAttribute()
    {
        return 'images/character-updates/'.floor($this->id / 1000);
    }

    /**
     * Gets the file name of the model's image.
     *
     * @return string
     */
    public function getImageFileNameAttribute()
    {
        return $this->id . '_'.$this->hash.'.'.$this->extension;
    }

    /**
     * Gets the path to the file directory containing the model's image.
     *
     * @return string
     */
    public function getImagePathAttribute()
    {
        return public_path($this->imageDirectory);
    }
    
    /**
     * Gets the URL of the model's image.
     *
     * @return string
     */
    public function getImageUrlAttribute()
    {
        return asset($this->imageDirectory . '/' . $this->imageFileName);
    }

    /**
     * Gets the file name of the model's thumbnail image.
     *
     * @return string
     */
    public function getThumbnailFileNameAttribute()
    {
        return $this->id . '_'.$this->hash.'_th.'.$this->extension;
    }

    /**
     * Gets the path to the file directory containing the model's thumbnail image.
     *
     * @return string
     */
    public function getThumbnailPathAttribute()
    {
        return $this->imagePath;
    }
    
    /**
     * Gets the URL of the model's thumbnail image.
     *
     * @return string
     */
    public function getThumbnailUrlAttribute()
    {
        return asset($this->imageDirectory . '/' . $this->thumbnailFileName);
    }

    /**
     * Gets the URL of the design update request.
     *
     * @return string
     */
    public function getUrlAttribute()
    {
        return url('designs/'.$this->id);
    }

    /**********************************************************************************************
    
        OTHER FUNCTIONS

    **********************************************************************************************/

    /**
     * Get the available currencies that the user can attach to this update request.
     *
     * @param  string  $type
     * @return array
     */
    public function getBank($type)
    {
        if($type == 'user') $currencies = $this->userBank;
        else $currencies = $this->characterBank;
        if(!count($currencies)) return [];
        $ids = array_keys($currencies);
        $result = Currency::whereIn('id', $ids)->get();
        foreach($result as $i=>$currency)
        {
            $currency->quantity = $currencies[$currency->id];
        }
        return $result;
    }
}