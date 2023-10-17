<?php

namespace Modules\FreeFeatureRequests\Entities;

use Illuminate\Database\Eloquent\Model;
use Watson\Rememberable\Rememberable;
use Modules\FreeFeatureRequests\Providers\FeatureRequestsServiceProvider;

class FeatureRequest extends Model
{
    public $timestamps = false;

    protected $fillable = [
    	'key', 'category', 'url', 'status', 'summary'
    ];

    public static function createOrUpdate($data)
    {
    	return self::updateOrCreate([
            'key' => $data['key'] ?? ''
        ], $data);
    }

    public function getTitle()
    {
        return $this->key.' - '.$this->summary;
    }

    public function getUrl()
    {
        return $this->url;
    }
    public function getStatusName()
    {
        $statuses = FeatureRequestsServiceProvider::getMeta('statuses');
        if (empty($statuses) || !isset($statuses[$this->status])) {
            return '';
        } else {
            return $statuses[$this->status] ?? '';
        }
    }

    public static function conversationLinkedRequests($conversation_id)
    {
        return self::leftJoin('feature_request_conversation', function ($join) {
                $join->on('feature_request_conversation.feature_request_id', '=', 'feature_requests.id');
            })
            ->where('feature_request_conversation.conversation_id', $conversation_id)
            ->get();
    }
}
