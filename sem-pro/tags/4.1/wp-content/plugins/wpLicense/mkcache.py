#!/usr/bin/env python

import os
import sys

import urllib2

ROOT_URL = "http://api.creativecommons.org/rest/1.5/";
CACHE_PATH = "./static_xml"

CLASSES = ('standard', 'publicdomain', 'recombo')

# retrieve the class index
index = urllib2.urlopen(ROOT_URL)
file(os.path.join(CACHE_PATH, "classes"), 'w').write(index.read())

# retrieve the class questions
for lc in CLASSES:
    questions = urllib2.urlopen(ROOT_URL + 'license/' + lc)
    file(os.path.join(CACHE_PATH, 'license', lc), 'w').write(questions.read())

    
