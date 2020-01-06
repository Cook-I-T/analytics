<?php
declare(strict_types=1);
/**
 * Data Analytics
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the LICENSE.md file.
 *
 * @author Marcel Scherello <audioplayer@scherello.de>
 * @copyright 2019 Marcel Scherello
 */

namespace OCA\Analytics\Flow;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\ILogger;
use OCP\Util;
use OCP\WorkflowEngine\EntityContext\IDisplayText;
use OCP\WorkflowEngine\EntityContext\IUrl;
use OCP\WorkflowEngine\IEntity;
use OCP\WorkflowEngine\IManager as FlowManager;
use OCP\WorkflowEngine\IOperation;
use OCP\WorkflowEngine\IRuleMatcher;
use Symfony\Component\EventDispatcher\GenericEvent;
use UnexpectedValueException;

class Operation implements IOperation
{

    private $logger;

    public function __construct(
        ILogger $logger
    )
    {
        $this->logger = $logger;
    }

    public static function register(IEventDispatcher $dispatcher): void
    {
        $dispatcher->addListener(FlowManager::EVENT_NAME_REG_OPERATION, function (GenericEvent $event) {
            $operation = \OC::$server->query(Operation::class);
            $event->getSubject()->registerOperation($operation);
            Util::addScript('analytics', 'flow');
        });
    }

    public function getDisplayName(): string
    {
        return $this->l->t('Write to Analytics');
    }

    public function getDescription(): string
    {
        return $this->l->t('Writes data to report');
    }

    public function getIcon(): string
    {
        return $this->urlGenerator->imagePath('analytics', 'app.svg');
    }

    public function isAvailableForScope(int $scope): bool
    {
        return true;
    }

    /**
     * Validates whether a configured workflow rule is valid. If it is not,
     * an `\UnexpectedValueException` is supposed to be thrown.
     *
     * @throws UnexpectedValueException
     * @since 9.1
     */
    public function validateOperation($name, array $checks, $operation): void
    {
    }

    public function onEvent(string $eventName, Event $event, IRuleMatcher $ruleMatcher): void
    {
        $this->logger->debug("Test Flow Operation");
    }
}