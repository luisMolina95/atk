<?php

namespace Sintattica\Atk\Core;

use Sintattica\Atk\Core\Menu\Menu;
use Sintattica\Atk\Core\Menu\MenuBase;

/**
 * The Module abstract base class.
 *
 * All modules in an ATK application should derive from this class
 */
abstract class Module
{

    /** @var Atk $atk */
    private $atk;

    /** @var MenuBase $menu */
    private $menu;

    private static $module;

    public function __construct(Atk $atk, MenuBase $menu)
    {
        $this->atk = $atk;
        $this->menu = $menu;
    }

    abstract public function register();

    public static function setModuleName($value): void
    {
        self::$module = $value;
    }

    protected function getMenu(): MenuBase
    {
        return $this->menu;
    }

    protected function getAtk(): Atk
    {
        return $this->atk;
    }

    public function boot()
    {
        //noop
    }

    public function registerNode($nodeName, $nodeClass, $actions = null)
    {
        $this->atk->registerNode(self::$module . '.' . $nodeName, $nodeClass, $actions);
    }

    /**
     * @throws \ReflectionException
     */
    public function addNodeToMenu($menuName, $nodeName, $action, $parent = 'main', $enable = null, $order = 0, $position = MenuBase::MENU_SIDEBAR)
    {
        if ($enable === null) {
            $enable = [self::$module . '.' . $nodeName, $action];
        }
        $this->menu->addMenuItem($menuName, Tools::dispatch_url(self::$module . '.' . $nodeName, $action), $parent, $enable,
            $order, self::$module, '', $position);
    }

    /**
     * @throws \ReflectionException
     */
    public function addMenuItem($name = '', $url = '', $parent = 'main', $enable = 1)
    {
        $this->menu->addMenuItem($name, $url, $parent, $enable, 0, self::$module);
    }
}
