<?php


namespace tweet9ra\Logux;


class SubscriptionMapper
{
    public function match(array $subscriptionMap, string $channel) : array
    {
        $matchedCallback = false;
        $arguments = [];

        foreach ($subscriptionMap as $key => $callback) {
            $regexp = str_replace('/', '\/', $key);
            $regexp = '/'.preg_replace('/:[^\\\\\/]+/m', '([^\/]+)', $regexp).'/m';
            preg_match_all($regexp, $channel, $matches, PREG_SET_ORDER, 0);

            if ($matches) {
                $arguments = $matches[0];
                array_shift($arguments);
                $matchedCallback = $callback;
                break;
            }
        }

        return [$matchedCallback, $arguments];
    }
}