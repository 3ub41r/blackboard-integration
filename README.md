# What is this?

This is a set of PHP scripts to import data to Blackboard.

## How to run

You need to build the container first:

```
docker build -t blackboard .
```

Then run the container:

```
docker run --rm blackboard
```

You can also mount the `src` directory:

```
docker run --rm -it -v $(pwd)/src:/usr/src/myapp/src blackboard bash
```


## Installing additional composer packages

Run the container and bash into it. Make sure you mount the current directory:

```
docker run --rm -it -v $(pwd)/src:/usr/src/myapp/src blackboard bash
composer require vlucas/phpdotenv
```

