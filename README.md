# Guzzle Bundle [![Build Status](https://github.com/ludofleury/GuzzleBundle/actions/workflows/tests.yml/badge.svg?branch=master)](https://github.com/ludofleury/GuzzleBundle/actions/workflows/tests.yml) [![Latest Stable Version](https://poser.pugx.org/playbloom/guzzle-bundle/v/stable.png)](https://packagist.org/packages/playbloom/guzzle-bundle) [![Total Downloads](https://poser.pugx.org/playbloom/guzzle-bundle/downloads.png)](https://packagist.org/packages/playbloom/guzzle-bundle)

Provide a basic logger and an advanced profiler for Guzzle with support for multiple Guzzle versions.

* The basic logger uses the default Symfony app logger, it's safe to use in your production environment.
* The advanced profiler is for debug purposes and will display a dedicated report available in the toolbar and Symfony Web Profiler
* **Multi-version support**: Automatically detects and works with Guzzle 3.x, 4.x, 5.x, 6.x, and 7.x

<img src="http://ludofleury.github.io/GuzzleBundle/images/guzzle-profiler-panel.png" width="280" height="175" alt="Guzzle Symfony web profiler panel"/>
<img src="http://ludofleury.github.io/GuzzleBundle/images/guzzle-request-detail.png" width="280" height="175" alt="Guzzle Symfony web profiler panel - request details"/>
<img src="http://ludofleury.github.io/GuzzleBundle/images/guzzle-response-detail.png" width="280" height="175" alt="Guzzle Symfony web profiler panel - response details"/>

## Supported Guzzle Versions

This bundle supports the following Guzzle versions:

* **Guzzle 3.x** (`guzzle/guzzle:~3.0`)
* **Guzzle 4.x** (`guzzlehttp/guzzle:~4.0`)
* **Guzzle 5.x** (`guzzlehttp/guzzle:~5.0`)
* **Guzzle 6.x** (`guzzlehttp/guzzle:~6.0`)
* **Guzzle 7.x** (`guzzlehttp/guzzle:~7.0`)

The bundle automatically detects which version you have installed and adapts accordingly.

## Installation

```sh
composer require --dev playbloom/guzzle-bundle
```

The bundle requires one of the supported Guzzle versions to be installed in your project:

```sh
# For Guzzle 3
composer require guzzle/guzzle:~3.0

# For Guzzle 6+ (recommended)
composer require guzzlehttp/guzzle:~6.0
```

Add the bundle to your Symfony app kernel

```php
<?php
    // in %your_project%/app/AppKernel.php
    $bundles[] = new Playbloom\Bundle\GuzzleBundle\PlaybloomGuzzleBundle();
?>
```

To enable the advanced profiler & the toolbar/web profiler panel, add this line to your `app/config/config_dev.yml`

```yml
playbloom_guzzle:
    web_profiler: true
```

### Guzzle client as a Symfony service

Concrete [Guzzle client creation](http://guzzle.readthedocs.org/en/latest/clients.html#creating-a-client) can be easily managed by the Symfony service container thanks to a [simple factory configuration](http://symfony.com/doc/current/components/dependency_injection/factories.html), in this case, you just need to tag your guzzle service(s) with `playbloom_guzzle.client`.

**Automatic plugin attachment** via service tags works for all Guzzle versions (3, 4, 5, 6, and 7). It will add the basic logger to your client(s). If the web_profiler is enabled in the current environment, it will also add the advanced profiler and display report on the Symfony toolbar/web profiler.

```yaml
# config/services.yaml
services:
    acme.client:
        class: '%acme.client.class%'
        factory: ['%acme.client.class%', 'factory']
        tags: ['playbloom_guzzle.client']
```

### Add the logger/profiler manually to a Guzzle client

If you need to handle the registration of the logger or profiler manually, you can retrieve these services from the Symfony container.

#### For Guzzle 3 (Backward Compatible API)

```php
$client = new \Guzzle\Http\Client('https://api.example.com');

// Add logger (logs to Symfony's default logger in the 'guzzle' channel)
$loggerPlugin = $container->get('playbloom_guzzle.client.plugin.logger');
$client->addSubscriber($loggerPlugin);

// Add profiler for development/debug (requires web_profiler to be enabled)
$profilerPlugin = $container->get('playbloom_guzzle.client.plugin.profiler');
$client->addSubscriber($profilerPlugin);
```

#### For Guzzle 4 and 5

```php
use GuzzleHttp\Client;

$client = new Client(['base_url' => 'https://api.example.com']);

// Add logger (logs to Symfony's default logger in the 'guzzle' channel)
$loggerSubscriber = $container->get('playbloom_guzzle.client.plugin.logger');
$client->getEmitter()->attach($loggerSubscriber);

// Add profiler for development/debug (requires web_profiler to be enabled)
$profilerSubscriber = $container->get('playbloom_guzzle.client.plugin.profiler');
$client->getEmitter()->attach($profilerSubscriber);
```

#### For Guzzle 6+

```php
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;

$stack = HandlerStack::create();

// Add logger (logs to Symfony's default logger in the 'guzzle' channel)
$loggerMiddleware = $container->get('playbloom_guzzle.client.plugin.logger');
$stack->push($loggerMiddleware);

// Add profiler for development/debug (requires web_profiler to be enabled)
$profilerMiddleware = $container->get('playbloom_guzzle.client.plugin.profiler');
$stack->push($profilerMiddleware);

$client = new Client([
    'base_uri' => 'https://api.example.com',
    'handler' => $stack
]);
```

## Customize your own profiler panel

If you need a [custom profiler panel](http://symfony.com/doc/master/cookbook/profiler/data_collector.html) you can extend/reuse easily the data collector and profiler template from this bundle.

For example, you have a GithubBundle which interact with the Github API. You also have a Github profiler panel to debug your developement and you want to have the API requests profiled in this panel.

It's quite easy:
First, define your own `GithubDataCollector` extending the `Playbloom\Bundle\GuzzleBundle\DataCollector\GuzzleDataCollector`


Then extends the guzzle web profiler template

```twig
{% extends 'PlaybloomGuzzleBundle:Collector:guzzle.html.twig' %}

{% block panel %}
    <div class="github">
        <h2>Github</h2>
        <ul>
            <li><strong>Github API key:</strong> {{ collector.getApiKey }}</li>
            <!-- Some custom information -->
        </ul>
    </div>

    {% include 'PlaybloomGuzzleBundle:Profiler:requests.html.twig' with {'requests': collector.requests } %}
{% endblock %}
```

And finally declare your data collector

```yaml
# config/services.yaml
services:
    data_collector.github:
        class: Acme\GithubBundle\DataCollector\GithubDataCollector
        arguments:
            - '@playbloom_guzzle.client.plugin.profiler'
        tags:
            - { name: data_collector, template: '@AcmeGithub/Collector/github.html.twig', id: github }
```

That's it, now your profiler panel displays your custom information and the Guzzle API requests.

## TODO

* Add extra information about the client configuration itself (thanks to the guzzle service builder?)
* Add clients|host|endpoint|time filters for http requests

## Licence

This bundle is under the MIT license. See the complete license in the bundle

## Credits

* Swagger for the UI
