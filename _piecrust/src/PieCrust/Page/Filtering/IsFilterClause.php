<?php

namespace PieCrust\Page\Filtering;

use PieCrust\IPage;


/**
 * Filter clause for having a specific setting value.
 */
class IsFilterClause extends FilterClause
{
    public function __construct($settingName, $settingValue)
    {
        FilterClause::__construct($settingName, $settingValue);
    }
    
    public function postMatches(IPage $post)
    {
        $actualValue = $post->getConfig()->getValue($this->settingName);
        return $actualValue != null && $actualValue == $this->settingValue;
    }
}
