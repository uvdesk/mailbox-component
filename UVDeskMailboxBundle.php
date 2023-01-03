<?php

namespace Webkul\UVDesk\MailboxBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Webkul\UVDesk\MailboxBundle\DependencyInjection\UVDeskExtension;

class UVDeskMailboxBundle extends Bundle
{
    public function getContainerExtension()
    {
        return new UVDeskExtension();
    }

    public function build(ContainerBuilder $container)
    {
        parent::build($container);
    }
}
