# Getting Started
## Install packages

Symfony package

```bash 
 composer install
```

Javascript package

```bash 
 yarn install
```

Start the app

```bash
symfony server:start
```

## Implementation

We first created the service `FsService` in `src\Service`. This service has the method 
`getVisitor` which will initialize the SDK, create a visitor instance, fetch flags then 
return the newly created visitor.

```php
<?php

namespace App\Service;

use Flagship\Config\FlagshipConfig;
use Flagship\Enum\CacheStrategy;
use Flagship\Flagship;
use Flagship\Visitor\VisitorInterface;
use Psr\Log\LoggerInterface;

class FsService
{
    private string $fsEnvId;
    private string $fsApiKey;
    private LoggerInterface $logger;

    public function __construct(string $fsEnvId, string $fsApiKey, LoggerInterface $logger)
    {

        $this->fsEnvId = $fsEnvId;
        $this->fsApiKey = $fsApiKey;
        $this->logger = $logger;
    }

    public function getVisitor(): VisitorInterface
    {
        // start the SDK in Decision API mode
        Flagship::start($this->fsEnvId, $this->fsApiKey,
            FlagshipConfig::decisionApi()
                ->setCacheStrategy(CacheStrategy::BATCHING_AND_CACHING_ON_FAILURE)
                ->setLogManager($this->logger)); // Set to send logs to symfony logger

        //Create a visitor instance
        $visitor = Flagship::newVisitor("visitor")
            ->build();

        //Fetch flags
        $visitor->fetchFlags();

        // Return the visitor instance
        return $visitor;
    }
}
```

In `config/service.yaml` config, we set envID and apiKey as argument of `FsService`

```yaml

    ....
    
    App\Service\FsService:
        arguments:
            $fsEnvId: '%env(string:FS_ENV_ID)%'
            $fsApiKey: '%env(string:FS_API_KEY)%'

    ...
```

Now we can use the `FsService` in the controller `src/Controller/HomePageController`, get the visitor instance
, do other things with it if needed, then call `getFlagsDto()` method to get visitor flags to pass them to `Flagship Javascript SDK`

```php
<?php

// Define the namespace for this file
namespace App\Controller;

// Import the necessary classes
use App\Service\FsService;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

// Define the HomePageController class, which extends the AbstractController class
class HomePageController extends AbstractController
{
    // Define the index method, which is mapped to the '/' route
    #[Route('/', name: 'app_home_page')]
    public function index(FsService $fsService, Request $request): Response
    {
        // Get the visitor instance from the FsService
        $visitor = $fsService->getVisitor();

        // Do other stuff with visitor instance if needed

        // Render the 'home_page/index.html.twig' template with the specified parameters
        $response = $this->render('home_page/index.html.twig', [
            'controller_name' => 'HomePageController',
            'fsVisitorFlags' => json_encode($visitor->getFlagsDTO()) // Pass Flags in the params of the template
        ]);

        // Create a new cookie for the visitor ID
        $cookie = new Cookie("fsVisitorId", $visitor->getVisitorId());
        // Set the HttpOnly flag to false
        $cookie = $cookie->withHttpOnly(false);
        // Add the cookie to the response headers
        $response->headers->setCookie($cookie);
        // Return the response
        return $response;
    }
}

```

In the template `templates/home_page/index.html.twig`, in a tag `script` we set `fsVisitorFlags` to the `initialFlagsData`

```html
{# Extend the base layout #}
{% extends 'base.html.twig' %}

{# Set the title of the page #}
{% block title %}Hello HomePageController!{% endblock %}

{# Define the body content of the page #}
{% block body %}
<style>
    .example-wrapper { margin: 1em auto; max-width: 800px; width: 95%; font: 18px/1.5 sans-serif; }
    .example-wrapper code { background: #F5F5F5; padding: 2px 6px; }
</style>

<div class="example-wrapper">
    {# Display a greeting message with the controller name #}
    <h1>Hello {{ controller_name }}! ✅</h1>
    Flags:
    <ul>
        {# Display the flag value and metadata #}
        <li> myFlagKey :<span id="myFlagValue"> </span></li>
        <li >metaData : <span id="myFlagMetaData"></span> </li>
    </ul>
</div>
{% endblock %}

{# Define the JavaScript content of the page #}
{% block javascripts %}
{# Call the parent block content #}
{{ parent() }}

{# Initialize the constant `initialFlagsData` in JavaScript to set fsVisitorFlags #}
<script> const initialFlagsData = {{ fsVisitorFlags|raw }}</script>

{# Include the JS script where the Flagship JS SDK is initialized #}
{{ encore_entry_script_tags('boostrapFlagship') }}
{% endblock %}

```

Finally, we can use the `visitor flags data` to initialize a visitor client side

`assets/boostrapFlagship.js`

```javascript
// Import the Flagship module from the Flagship JavaScript SDK
import {Flagship} from  '@flagship.io/js-sdk'

// Define a function to get the value of a specific cookie
const getCookieValue = (cname) => {
    let name = cname + "=";
    // Decode the cookie string to handle cookies with special characters, e.g. '$'
    let decodedCookie = decodeURIComponent(document.cookie);
    // Split document.cookie on semicolons into an array
    let ca = decodedCookie.split(';');
    // Loop over the array of cookies
    for(let i = 0; i <ca.length; i++) {
        let c = ca[i];
        // If the cookie starts with a space, remove the space
        while (c.charAt(0) === ' ') {
            c = c.substring(1);
        }
        // If the cookie name is found at the start of the cookie (c), return the cookie value
        if (c.indexOf(name) === 0) {
            return c.substring(name.length, c.length);
        }
    }
    // If the cookie is not found, return an empty string
    return "";
}

// Add an event listener to the window load event
window.addEventListener('load', ()=>{
    // Start the Flagship SDK with the specified environment ID and API key
    Flagship.start("envId", "apiKey", {
        fetchNow: false // set FetchNow to false
    })

    // Get the visitor ID from the cookies
    const visitorId = getCookieValue("fsVisitorId")

    // Create a new visitor instance with the visitor ID and initial flags data
    const visitor = Flagship.newVisitor({
        visitorId,
        initialFlagsData: initialFlagsData // Set the initial flags data
    });

    // Get the flag value for "my_flag", with a default value specified
    const flag = visitor.getFlag("my_flag", "defaultValue")

    // Get the HTML element with the ID "myFlagValue" and set its inner text to the flag value
    const myFlagValue = document.getElementById("myFlagValue")
    myFlagValue.innerText = flag.getValue()

    // Get the HTML element with the ID "myFlagMetaData" and set its inner text to the flag metadata
    const myFlagMetaData = document.getElementById("myFlagMetaData")
    myFlagMetaData.innerText = JSON.stringify(flag.metadata, null, 4)

})

```

We create an event subscriber to event `Kernel:terminate` to send batch and send collected hits  

```php
<?php

namespace App\EventListener;

use Flagship\Flagship;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class KernelTerminateListener implements EventSubscriberInterface
{
    public function onKernelTerminate(TerminateEvent $event): void
    {
        //Batch and send collected hits 
        Flagship::close();
    }

    public static function getSubscribedEvents(): array
    {
        return  [
            KernelEvents::TERMINATE => [ 'onKernelTerminate']
        ];
    }
}
```