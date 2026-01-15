<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

declare(strict_types=1);

namespace ILIAS;

use ILIAS\ApiGateway\Activity\ActivityNamespaceFactory;
use ILIAS\ApiGateway\Activity\ActivityRouteFactory;
use ILIAS\ApiGateway\Activity\ActivityRoutesAutoloader;
use ILIAS\ApiGateway\Application\Factory\HttpConfigFactory;
use ILIAS\ApiGateway\Application\Factory\HttpServiceFactory;
use ILIAS\ApiGateway\Application\Factory\WebAppFactory;
use ILIAS\ApiGateway\Auth;
use ILIAS\ApiGateway\Auth\Domain\Repository\UserRepository;
use ILIAS\ApiGateway\Auth\Domain\Service\Authentication;
use ILIAS\ApiGateway\Auth\Infrastructure\DatabaseRefreshTokenRepository;
use ILIAS\ApiGateway\Auth\Infrastructure\DatabaseUserRepository;
use ILIAS\ApiGateway\Configuration;
use ILIAS\ApiGateway\Logging\WebserviceLoggerFactory;
use ILIAS\ApiGateway\Middleware\MiddlewareRepository;
use ILIAS\ApiGateway\RestAppEntryPoint;
use ILIAS\ApiGateway\Routing\Route;
use ILIAS\ApiGateway\Routing\RoutesAutoloader;
use ILIAS\ApiGateway\Routing\RoutesRegistry;
use ILIAS\ApiGateway\Routing\RouteStaticRepository;
use ILIAS\ApiGateway\Webservice\WebserviceFactory;
use ILIAS\Component\Activities\Activity;
use Psr\Http\Server\MiddlewareInterface;

class ApiGateway implements Component\Component
{
    #[\Override]
    public function init(
        array | \ArrayAccess &$define,
        array | \ArrayAccess &$implement,
        array | \ArrayAccess &$use,
        array | \ArrayAccess &$contribute,
        array | \ArrayAccess &$seek,
        array | \ArrayAccess &$provide,
        array | \ArrayAccess &$pull,
        array | \ArrayAccess &$internal,
    ): void {
        // declare component setup (ILIAS install/update)
        $contribute[\ILIAS\Setup\Agent::class] = fn() =>
        new ApiGateway\Setup\ApiGatewaySetupAgent(
            $pull[\ILIAS\Refinery\Factory::class],
        );

        $contribute[Component\Resource\PublicAsset::class] = fn(): Component\Resource\Endpoint =>
        new Component\Resource\Endpoint($this, "rest/index.php", "rest");
        $contribute[Component\Resource\PublicAsset::class] = fn(): Component\Resource\OfComponent =>
        new Component\Resource\OfComponent($this, "rest/.htaccess", "rest");

        // @todo: should be fixed in original component
        $implement[HTTP\Response\ResponseFactory::class] = static fn(): HTTP\Response\ResponseFactory =>
        new HTTP\Response\ResponseFactoryImpl();

        /**
         * Main declarations to be consumed by other components
         */
        $define[] = ApiGateway\Routing\Route::class;

        ///////////////////////////////////////////////////////////////////////////////////
        //////////////////////////////// Application Layer ////////////////////////////////
        ///////////////////////////////////////////////////////////////////////////////////

        $internal[WebAppFactory::class] = static fn(): WebAppFactory =>

        new WebAppFactory(
            $internal[HttpConfigFactory::class],
            new HttpServiceFactory(),
            new WebserviceFactory(),
            $internal[RoutesRegistry::class],
            new RoutesAutoloader(
                $internal[RoutesRegistry::class],
                new RouteStaticRepository(
                    $seek[ApiGateway\Routing\Route::class],
                ),
            ),
            new ActivityRoutesAutoloader(
                $internal[RoutesRegistry::class],
                $use[Component\Activities\Repository::class],
                $internal[ActivityRouteFactory::class],
            ),
            new MiddlewareRepository(
                $seek[MiddlewareInterface::class],
            ),
            $use[HTTP\Response\ResponseFactory::class],
            new WebserviceLoggerFactory(),
        );

        ///////////////////////////////////////////////////////////////////////////////////
        ///////////////////////////// Service Contributions ///////////////////////////////
        ///////////////////////////////////////////////////////////////////////////////////

        /**
         * Middlewares
         */
        $contribute[MiddlewareInterface::class] = static fn(): MiddlewareInterface =>
        new ApiGateway\Middleware\AuthenticationMiddleware(
            $internal[Authentication::class],
        );

        /**
         * API Application Endpoints
         */

        ## /ping
        $contribute[Route::class] = static fn(): Route => new ApiGateway\Routes\PingRoute();

        ## /activities
        $contribute[Route::class] = static fn(): Route => new ApiGateway\Examples\GetActivityListApiAction(
            $use[Component\Activities\Repository::class],
            $internal[ActivityRouteFactory::class],
            'rest',
        );

        ## /auth/token
        $contribute[Route::class] = static fn(): Route =>
        new ApiGateway\Routes\Auth\IssueTokenRoute(
            $internal[Authentication::class],
            $internal[UserRepository::class],
        );

        ## /auth/refresh
        $contribute[Route::class] = static fn(): Route =>
        new ApiGateway\Routes\Auth\RefreshTokenRoute(
            $internal[Authentication::class],
        );

        ///////////////////////////////////////////////////////////////////////////////////
        ////////////////////////// Main Application Entry Points //////////////////////////
        ///////////////////////////////////////////////////////////////////////////////////

        $contribute[Component\EntryPoint::class] = static fn(): Component\EntryPoint =>
        new RestAppEntryPoint(
            $internal[WebAppFactory::class],
            $pull[\ILIAS\Refinery\Factory::class],
            $pull[\ILIAS\Data\Factory::class],
            $use[\ILIAS\UI\Factory::class],
            $use[\ILIAS\UI\Renderer::class],
            $pull[\ILIAS\UI\Implementation\Component\Counter\Factory::class],
            $pull[\ILIAS\UI\Implementation\Component\Button\Factory::class],
            $pull[\ILIAS\UI\Implementation\Component\Listing\Factory::class],
            $pull[\ILIAS\UI\Implementation\Component\Listing\Workflow\Factory::class],
            $pull[\ILIAS\UI\Implementation\Component\Listing\CharacteristicValue\Factory::class],
            $pull[\ILIAS\UI\Implementation\Component\Listing\Entity\Factory::class],
            $pull[\ILIAS\UI\Implementation\Component\Image\Factory::class],
            $pull[\ILIAS\UI\Implementation\Component\Player\Factory::class],
            $pull[\ILIAS\UI\Implementation\Component\Panel\Factory::class],
            $pull[\ILIAS\UI\Implementation\Component\Modal\Factory::class],
            $pull[\ILIAS\UI\Implementation\Component\Dropzone\Factory::class],
            $pull[\ILIAS\UI\Implementation\Component\Popover\Factory::class],
            $pull[\ILIAS\UI\Implementation\Component\Divider\Factory::class],
            $pull[\ILIAS\UI\Implementation\Component\Link\Factory::class],
            $pull[\ILIAS\UI\Implementation\Component\Dropdown\Factory::class],
            $pull[\ILIAS\UI\Implementation\Component\Item\Factory::class],
            $pull[\ILIAS\UI\Implementation\Component\ViewControl\Factory::class],
            $pull[\ILIAS\UI\Implementation\Component\Chart\Factory::class],
            $pull[\ILIAS\UI\Implementation\Component\Input\Factory::class],
            $pull[\ILIAS\UI\Implementation\Component\Table\Factory::class],
            $pull[\ILIAS\UI\Implementation\Component\MessageBox\Factory::class],
            $pull[\ILIAS\UI\Implementation\Component\Card\Factory::class],
            $pull[\ILIAS\UI\Implementation\Component\Layout\Factory::class],
            $pull[\ILIAS\UI\Implementation\Component\Layout\Page\Factory::class],
            $pull[\ILIAS\UI\Implementation\Component\Layout\Alignment\Factory::class],
            $pull[\ILIAS\UI\Implementation\Component\MainControls\Factory::class],
            $pull[\ILIAS\UI\Implementation\Component\Tree\Factory::class],
            $pull[\ILIAS\UI\Implementation\Component\Tree\Node\Factory::class],
            $pull[\ILIAS\UI\Implementation\Component\Menu\Factory::class],
            $pull[\ILIAS\UI\Implementation\Component\Symbol\Factory::class],
            $pull[\ILIAS\UI\Implementation\Component\Toast\Factory::class],
            $pull[\ILIAS\UI\Implementation\Component\Legacy\Factory::class],
            $pull[\ILIAS\UI\Implementation\Component\Launcher\Factory::class],
            $pull[\ILIAS\UI\Implementation\Component\Entity\Factory::class],
            $pull[\ILIAS\UI\Implementation\Component\Panel\Listing\Factory::class],
            $pull[\ILIAS\UI\Implementation\Component\Panel\Secondary\Factory::class],
            $pull[\ILIAS\UI\Implementation\Component\Modal\InterruptiveItem\Factory::class],
            $pull[\ILIAS\UI\Implementation\Component\Chart\ProgressMeter\Factory::class],
            $pull[\ILIAS\UI\Implementation\Component\Chart\Bar\Factory::class],
            $pull[\ILIAS\UI\Implementation\Component\Input\ViewControl\Factory::class],
            $pull[\ILIAS\UI\Implementation\Component\Input\Container\ViewControl\Factory::class],
            $pull[\ILIAS\UI\Implementation\Component\Table\Column\Factory::class],
            $pull[\ILIAS\UI\Implementation\Component\Table\Factory::class],
            $pull[\ILIAS\UI\Implementation\Component\MainControls\Slate\Factory::class],
            $pull[\ILIAS\UI\Implementation\Component\Symbol\Icon\Factory::class],
            $pull[\ILIAS\UI\Implementation\Component\Symbol\Glyph\Factory::class],
            $pull[\ILIAS\UI\Implementation\Component\Symbol\Avatar\Factory::class],
            $pull[\ILIAS\UI\Implementation\Component\Input\Container\Form\Factory::class],
            $pull[\ILIAS\UI\Implementation\Component\Input\Container\Filter\Factory::class],
            $pull[\ILIAS\UI\Implementation\Component\Input\Field\Factory::class],
            $pull[\ILIAS\UI\Implementation\Component\Prompt\Factory::class],
            $pull[\ILIAS\UI\Implementation\Component\Prompt\State\Factory::class],
            $pull[\ILIAS\UI\Implementation\Component\Progress\Factory::class],
            $pull[\ILIAS\UI\Implementation\Component\Progress\State\Factory::class],
            $pull[\ILIAS\UI\Implementation\Component\Progress\State\Bar\Factory::class],
            $pull[\ILIAS\UI\Implementation\Component\Input\UploadLimitResolver::class],
            $use[\ILIAS\Setup\AgentFinder::class],
            $pull[\ILIAS\UI\Implementation\Component\Navigation\Factory::class],
        );

        ///////////////////////////////////////////////////////////////////////////////////
        //////////////////////////////// Shared internally ////////////////////////////////
        ///////////////////////////////////////////////////////////////////////////////////

        /**
         * @var ApiGateway\Application\Factory\HttpConfigFactory
         *
         * workaround @todo: replace with DI then replace usage with config objects
         */
        $internal[HttpConfigFactory::class] = static fn(): HttpConfigFactory =>
        new HttpConfigFactory(
            $internal[Configuration\Domain\Configuration::class],
        );

        ///////////////////////////////////////////////////////////////////////////////////
        /////////////////////////////////// SUB-MODULES ///////////////////////////////////
        ///////////////////////////////////////////////////////////////////////////////////


        // MODULE: Activity

        $internal[ActivityRouteFactory::class] = static fn(): ActivityRouteFactory =>
        new ActivityRouteFactory(
            new ActivityNamespaceFactory(),
        );

        // MODULE: Auth

        $internal[UserRepository::class] = static fn(): UserRepository => new DatabaseUserRepository();
        $internal[Authentication::class] = static fn(): Authentication =>
        new Auth\Infrastructure\AuthService(
            new Auth\Infrastructure\JwtService(
                $internal[HttpConfigFactory::class], // workaround @todo: replace with DI
            ),
            $internal[UserRepository::class],
            new DatabaseRefreshTokenRepository(),
            $internal[HttpConfigFactory::class], // workaround @todo: replace with DI
        );

        // MODULE: Configuration

        $internal[Configuration\Domain\Configuration::class] = static fn(): Configuration\Domain\Configuration =>
        new Configuration\Infrastructure\ConfigurationService(
            new Configuration\Infrastructure\Repository\AdminSettings(),
        );

        // MODULE: Routing

        $internal[RoutesRegistry::class] = fn(): RoutesRegistry => RoutesRegistry::getInstance();
    }
}
