<?php

declare(strict_types=1);

// Extension methods of the form containers are registered by installing the package (composer
// autoload "files"), so that no project has to call register() in its bootstrap. Calling it
// again is harmless - extensionMethod() just stores the same callback.
ADT\Forms\BaseContainer::register();
ADT\Forms\Controls\PasswordRevealInput::register();
