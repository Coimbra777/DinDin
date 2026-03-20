<?php

function HelperAssets($image)
{
    if (config('constants.options.APP_ENV') === 'local') {
        return 'http://'.gethostbyname(gethostname()).':8000/'.$image;
    }

    return asset($image);
}
