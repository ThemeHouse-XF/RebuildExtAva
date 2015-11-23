<?php

class ThemeHouse_RebuildExtAv_Listener_LoadClass extends ThemeHouse_Listener_LoadClass
{

    protected function _getExtendedClasses()
    {
        return array(
            'ThemeHouse_RebuildExtAv' => array(
                'datawriter' => array(
                    'XenForo_DataWriter_User'
                ), /* END 'datawriter' */
                'deferred' => array(
                    'XenForo_Deferred_User'
                ), /* END 'deferred' */
            ), /* END 'ThemeHouse_RebuildExtAv' */
        );
    } /* END _getExtendedClasses */

    public static function loadClassDataWriter($class, array &$extend)
    {
        $extend = self::createAndRun('ThemeHouse_RebuildExtAv_Listener_LoadClass', $class, $extend, 'datawriter');
    } /* END loadClassDataWriter */

    public static function loadClassDeferred($class, array &$extend)
    {
        $extend = self::createAndRun('ThemeHouse_RebuildExtAv_Listener_LoadClass', $class, $extend, 'deferred');
    } /* END loadClassDeferred */
}