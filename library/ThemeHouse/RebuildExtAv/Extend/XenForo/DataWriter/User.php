<?php

/**
 *
 * @see XenForo_DataWriter_User
 */
class ThemeHouse_RebuildExtAv_Extend_XenForo_DataWriter_User extends XFCP_ThemeHouse_RebuildExtAv_Extend_XenForo_DataWriter_User
{

    /**
     *
     * @see XenForo_DataWriter_User::_preSave()
     */
    protected function _preSave()
    {
        parent::_preSave();

        if (!empty($GLOBALS['XenForo_Deferred_User'])) {
            if (XenForo_Application::get('options')->gravatarEnable && $this->get('gravatar')) {
                // has a gravatar, ignore
            } elseif ($this->get('avatar_date')) {
                // has a regular avatar, ignore
            } else {
                $this->_fetchAvatarsFromExternalSites();
            }
        }
    } /* END _preSave */

    protected function _fetchAvatarsFromExternalSites()
    {
        /* @var $externalAuthModel XenForo_Model_UserExternal */
        $externalAuthModel = $this->getModelFromCache('XenForo_Model_UserExternal');

        $external = $externalAuthModel->getExternalAuthAssociationsForUser($this->get('user_id'));

        $fbUser = false;
        if (!empty($external['facebook'])) {
            $extra = @unserialize($external['twitter']['extra_data']);
            if (!empty($extra['token'])) {
                $avatarData = XenForo_Helper_Facebook::getUserPicture($extra['token']);
                if ($avatarData && $this->_applyAvatar($avatarData)) {
                    return true;
                }
            }
        }

        $twitterUser = false;
        if (!empty($external['twitter'])) {
            $extra = @unserialize($external['twitter']['extra_data']);
            if (!empty($extra['token'])) {
                $credentials = XenForo_Helper_Twitter::getUserFromToken($extra['token'], $extra['secret']);
                if (!empty($credentials['profile_image_url'])) {
                    try {
                        // get the original size
                        $url = str_replace('_normal', '', $credentials['profile_image_url']);
                        $request = XenForo_Helper_Http::getClient($url)->request();
                        $avatarData = $request->getBody();
                    } catch (Exception $e) {
                        $avatarData = '';
                    }
                    if ($avatarData && $this->_applyAvatar($avatarData)) {
                        return true;
                    }
                }
            }
        }

        $externalExtendedHelpers = array(
            'battlenet' => 'BattleNet',
            'github' => 'GitHub',
            'linkedin' => 'LinkedIn',
            'live' => 'Live',
            'odnoklassniki' => 'Odnoklassniki',
            'soundcloud' => 'SoundCloud',
            'tumblr' => 'Tumblr',
            'twitch' => 'Twitch',
            'vk' => 'VK'
        );

        foreach ($externalExtendedHelpers as $provider => $class) {
            if (!empty($external[$provider])) {
                $extra = $external[$provider]['extra_data'];
                if (!empty($extra['token'])) {
                    $helper = $this->_getExternalExtendedHelper($class);
                    if ($helper->avatarExists) {
                        $eeUser = $helper->getUserInfo($extra['token']);
                        $avatarData = $helper->getAvatar($eeUser);
                        if ($avatarData && $this->_applyAvatar($avatarData)) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    } /* END _fetchAvatarsFromExternalSites */

    protected function _getExternalExtendedHelper($class)
    {
        if (strpos($class, '_') === false) {
            $class = 'ExternalExtended_Helper_' . $class;
        }

        $class = XenForo_Application::resolveDynamicClass($class);

        // create a dummy controller
        $request = new Zend_Controller_Request_Http();
        $response = new Zend_Controller_Response_Http();
        $routeMatch = new XenForo_RouteMatch();
        $controller = new XenForo_ControllerAdmin_Tools($request, $response, $routeMatch);

        return new $class($controller);
    } /* END _getExternalExtendedHelper */

    protected function _applyAvatar($data)
    {
        $success = false;
        if (!$data) {
            return false;
        }

        $avatarFile = tempnam(XenForo_Helper_File::getTempDir(), 'xf');
        if ($avatarFile) {
            file_put_contents($avatarFile, $data);

            try {
                $dwData = $this->getModelFromCache('XenForo_Model_Avatar')->applyAvatar($this->get('user_id'),
                    $avatarFile);
                if ($dwData) {
                    $this->bulkSet($dwData);
                }
                $success = true;
            } catch (XenForo_Exception $e) {
            }

            @unlink($avatarFile);
        }

        return $success;
    } /* END _applyAvatar */
}