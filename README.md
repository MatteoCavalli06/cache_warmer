# Cache Warmer

**Visit website starting from an initial url**

## What is a cache warmer?  
The Cache Warmer is a PHP script designed to crawl and visit web pages. It ensures that all pages of a website are preloaded into the cache, reducing response times for subsequent users.

## How to use
To use this script, it's obligatory to, after entering the file.php in the command line, subsequently input the URL of the webpage you want to visit. Optionally, you can also provide the maximum level to visit, the waiting time between each visit (sleep), and links that you don't want to visit.

**Show help:**

```
php cacheWarmer.php --help
```
With the following command, the terminal returns the correct syntax to make the script work. It's important to note that the following command isn't obligatory for the program to function.