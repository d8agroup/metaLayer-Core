from application import logger
from configuration import *
from adapters import nlp_adapter, ocr_adapter, yahooplacemaker_adapter, imaging_adapter
from adapters import objectdetectionface_adapter, sentiment_adapter

def datalayer_1(request):
    
    logger.info('datalayer_1 - METHOD STARTED with parameters: request:%s' % request)
    
    return_data = { 'service':'dataLayer', 'version':1 }
    
    if 'text' not in request.form:
        logger.error('datalayer_1 - ERROR post variable \'text\' not supplied')
        return ERROR_DATALAYER_NOTEXT
    
    text = request.form['text']
    
    return_data = {
        'service':'datalayer',
        'version':1,
        'status':'success'
    }
    
    return_data['datalayer'] = {
        'text':text,
        'tags':run_text_tagging(text),
        'locations':run_text_locations(text),
        'sentiment':run_text_sentiment(text)         
    }
    
    logger.info('datalayer_1 - METHOD ENDED')
    
    return return_data

def sentiment_1(request):
    logger.info('sentiment_1 - METHOD STARTED with parameters: request:%s' % request)
    
    if 'text' not in request.form:
        logger.error('sentiment_1 - ERROR post variable \'text\' not supplied')
        return ERROR_DATALAYER_NOTEXT
    
    text = request.form['text']
    
    return_data = {
        'service':'sentiment',
        'version':1,
        'status':'success'
    }
    
    return_data['datalayer'] = {
        'sentiment':run_text_sentiment(text)
    }
    
    logger.info('sentiment_1 - METHOD ENDED')
    
    return return_data

def tagging_1(request):
    logger.info('tagging_1 - METHOD STARTED with parameters: request:%s' % request)
    
    if 'text' not in request.form:
        logger.error('tagging_1 - ERROR post variable \'text\' not supplied')
        return ERROR_DATALAYER_NOTEXT
    
    text = request.form['text']
    
    return_data = {
        'service':'tagging',
        'version':1,
        'status':'success'
    }
    
    return_data['datalayer'] = {
        'tagging':run_text_tagging(text)
    }
    
    logger.info('tagging_1 - METHOD ENDED')
    
    return return_data

def locations_1(request):
    logger.info('locations_1 - METHOD STARTED with parameters: request:%s' % request)
    
    if 'text' not in request.form:
        logger.error('locations_1 - ERROR post variable \'text\' not supplied')
        return ERROR_DATALAYER_NOTEXT
    
    text = request.form['text']
    
    return_data = {
        'service':'locations',
        'version':1,
        'status':'success'
    }
    
    return_data['datalayer'] = {
        'locations':run_text_locations(text)
    }
    
    logger.info('locations_1 - METHOD ENDED')
    
    return return_data

def imglayer_1(request):
    
    logger.info('imglayer_1 - METHOD STARTED with parameters: request:%s' % request)
    
    image_id = "test" #TODO this needs to be DB identifier once there is a DB
    
    image = request.files['image']
    
    return_data = {
        'service':'imglayer',
        'version':1,
        'status':'success'
    }
    
    ocr_response = ocr_adapter(image, image_id)
    
    if ocr_response['status'] == 'success':
        text = ocr_response['text']
        test_response = {
            'text':text,
            'tags':run_text_tagging(text),
            'locations':run_text_locations(text),
            'sentiment':run_text_sentiment(text)         
        }
    else:
        test_response = {}
        
    objectdetectionface_response = objectdetectionface_adapter(image)
    
    if objectdetectionface_response['status'] == 'success':
        return_data['objectdetection'] = { 'faces':objectdetectionface_response['faces'] }
    else:
        return_data['objectdetection'] = { 'faces':{ } }

    return_data['datalayer'] = test_response
        
    return_data['imglayer'] = imaging_adapter(image)
    
    logger.info('imglayer_1 - METHOD ENDED')
    
    return return_data

def run_text_tagging(text):
    tags = []
    
    try:
        tags = nlp_adapter(text)
    except Exception, e:
        logger.error('datalayer_1 - ERROR the call the nlp service failed: %s' % e)
        if not MASK_ERRORS:
            raise e 
        
    return tags
    
def run_text_locations(text):
    locations = []
    
    try:
        locations = yahooplacemaker_adapter(text)
    except Exception, e:
        logger.error('datalayer_1 - ERROR the call the location service failed: %s' % e)
        if not MASK_ERRORS:
            raise e 
        
    return locations
        
def run_text_sentiment(text):
    sentiment = 0;

    try:
        sentiment = sentiment_adapter(text)
    except Exception, e:
        logger.error('datalayer_1 - ERROR the call the sentiment service failed: %s' % e)
        if not MASK_ERRORS:
            raise e 
    
    return sentiment