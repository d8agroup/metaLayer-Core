from configuration import *
from flask import json
import urllib2
import urllib
import StringIO

from poster.encode import multipart_encode, MultipartParam
from poster.streaminghttp import register_openers
from xml.dom.minidom import parseString

from PIL import Image

def nlp_adapter(text):
    url = SERVICE_ENDPOINTS['nlp']
    
    data = { 'text':text, 'key':'AAAABBBB' }
    
    data = urllib.urlencode(data)
    
    request = urllib2.Request(url, data=data)
    
    response = json.loads(urllib2.urlopen(request).read())
    
    return response
    
def ocr_adapter(image, image_id):
    url = SERVICE_ENDPOINTS['ocr']
    
    register_openers()

    items = []
    
    items.append(MultipartParam(name='image_id', value=image_id))
    
    items.append(MultipartParam(name='image', filename='image.tiff', fileobj=image))

    datagen, headers = multipart_encode(items)

    request = urllib2.Request(url, datagen, headers)

    response = json.loads(urllib2.urlopen(request).read())
      
    return response

def imaging_adapter(image):
    url = SERVICE_ENDPOINTS['imaging']
    
    register_openers()

    items = []
    
    items.append(MultipartParam(name='image', filename='image.tiff', fileobj=image))

    datagen, headers = multipart_encode(items)

    request = urllib2.Request(url, datagen, headers)

    response = json.loads(urllib2.urlopen(request).read())
      
    return response
    

def yahooplacemaker_adapter(text):
    url = SERVICE_ENDPOINTS['yahooplcemaker']
    
    data = { 
        'documentContent':text, 
        'documentType':'text/plain', 
        'appid':APIKEYS['yahooplacemaker']
    }
    
    data = urllib.urlencode(data)
    
    request = urllib2.Request(url, data=data)
    
    dom = parseString(urllib2.urlopen(request).read())
    
    places = []
    
    for element in dom.getElementsByTagName('placeDetails'):
        confidence = element.getElementsByTagName('confidence')[0].childNodes[0].nodeValue
        for place in element.getElementsByTagName('place'):
            p = {}
            if place.getElementsByTagName('name')[0].childNodes:
                p['name'] = place.getElementsByTagName('name')[0].childNodes[0].nodeValue
            p['latitude'] = float(place.getElementsByTagName('centroid')[0].getElementsByTagName('latitude')[0].childNodes[0].nodeValue)
            p['longitude'] = float(place.getElementsByTagName('centroid')[0].getElementsByTagName('longitude')[0].childNodes[0].nodeValue)
            p['confidence'] = float(confidence)
            places.append(p)
   
    return places
    
    