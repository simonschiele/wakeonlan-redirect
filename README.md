# wakeonlan-redirect

Small PHP page that sends wake-on-lan packages and redirects to a page when the host is available.
It includes a small frontend or can be used as a "jump in" directly.

The wol functions are not written by me, the source of the source can be found in a comment above each function.


## example usage

Most simple way: Just use the included frontend.

To use it directly build something like this:
http://home.dyndns.org/wake.php?mac=d2:50:9a:b6:ef:2b&broadcast=192.168.5.255&redirect=http://home.dyndns.org/tvrecorder


## Supported Parameters

You can use this page as a direct wol jump in to your page. GET and POST (untested) requests are supported.

Parameters:

* mac                     -   [example: d2:50:9a:b6:ef:2b]
* broadcast               -   [example: 192.168.5.255]
* [optional] redirect     -   [example: http://server.loc]
* [optional] debug        -   [true|false]


## Todo

*  cookies


## Author

*  simon (simon.codingmokey@googlemail.com)


