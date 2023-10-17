<?php

namespace Modules\FreeFeatureRequests\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\CustomFields\Entities\CustomField;

class FeatureRequestConversation extends Model
{
    protected $table = 'feature_request_conversation';
    
    public $timestamps = false;

    protected $fillable = [
    	'feature_request_id', 'conversation_id'
    ];

    /**
     * Get user.
     */
    public function conversation()
    {
        return $this->belongsTo('App\Conversation');
    }
}
