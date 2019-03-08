<?php

namespace MPHB;

use MPHB\Utils\DateUtils;

class BlocksRender
{
    public function renderSearch($atts)
    {
        return $this->renderShortcode(MPHB()->getShortcodes()->getSearch()->getName(), $atts);
    }

    public function renderAvailabilityCalendar($atts)
    {
        return $this->renderShortcode(MPHB()->getShortcodes()->getAvailabilityCalendar()->getName(), $atts);
    }

    public function renderSearchResults($atts)
    {
        return $this->renderShortcode(MPHB()->getShortcodes()->getSearchResults()->getName(), $atts);
    }

    public function renderRooms($atts)
    {
        return $this->renderShortcode(MPHB()->getShortcodes()->getRooms()->getName(), $atts);
    }

    public function renderServices($atts)
    {
        return $this->renderShortcode(MPHB()->getShortcodes()->getServices()->getName(), $atts);
    }

    public function renderRoom($atts)
    {
        return $this->renderShortcode(MPHB()->getShortcodes()->getRoom()->getName(), $atts);
    }

    public function renderCheckout($atts)
    {
        return $this->renderShortcode(MPHB()->getShortcodes()->getCheckout()->getName(), $atts);
    }

    public function renderBookingForm($atts)
    {
        return $this->renderShortcode(MPHB()->getShortcodes()->getBookingForm()->getName(), $atts);
    }

    public function renderRoomRates($atts)
    {
        return $this->renderShortcode(MPHB()->getShortcodes()->getRoomRates()->getName(), $atts);
    }

    public function renderBookingConfirmation($atts)
    {
        return $this->renderShortcode(MPHB()->getShortcodes()->getBookingConfirmation()->getName(), $atts);
    }

    protected function renderShortcode($name, $atts)
    {
        $shortcode = MPHB()->getShortcodes()->getShortcodeByName($name);

        if (is_null($shortcode)) {
            return '';
        }

        $atts = $this->filterAtts($atts);

        if (has_filter('the_content', 'wpautop') !== false) {
            remove_filter('the_content', 'wpautop');
            add_filter('the_content', function ($content) {
                if (has_blocks()) {
                    return $content;
                }

                return wpautop($content);
            });
        }

        return $shortcode->render($atts, '', $name);
    }

    protected function filterAtts($atts)
    {
        $atts = array_filter($atts, function ($value) {
            return $value !== '';
        });

        $class = '';

        if (isset($atts['className'])) {
            $class .= $atts['className'];
            unset($atts['className']);
        }

        if (isset($atts['alignment'])) {
            $class .= ' align' . $atts['alignment'];
            unset($atts['alignment']);
        }

        if (!empty($class)) {
            $atts['class'] = trim($class);
        }

        $dateFormat = MPHB()->settings()->dateTime()->getDateFormat();
        $dateRegex = DateUtils::dateFormatToRegex($dateFormat);

        foreach (array('check_in_date', 'check_out_date') as $attrName) {
            if (!isset($atts[$attrName])) {
                continue;
            }

            $isValid = (bool)preg_match($dateRegex, $atts[$attrName]);

            if (!$isValid) {
                unset($atts[$attrName]);
            }
        }

        return $atts;
    }
}
