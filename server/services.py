from application import logger
from configuration import *
from adapters import nlp_adapter, ocr_adapter, yahooplacemaker_adapter, imaging_adapter
from adapters import objectdetectionface_adapter

def datalayer_1(request):
    
    logger.info('datalayer_1 - METHOD STARTED with parameters: request:%s' % request)
    
    return_data = { 'service':'dataLayer', 'version':1 }
    
    if 'text' not in request.form:
        logger.error('datalayer_1 - ERROR post variable \'text\' not supplied')
        return ERROR_DATALAYER_NOTEXT
    
    text = request.form['text']
    
    return_data = {
        'service':'datalayer',
        'version':1
    }
    
    return_data['datalayer'] = run_text_processes(text)
    
    logger.info('datalayer_1 - METHOD ENDED')
    
    return return_data

def imglayer_1(request):
    
    logger.info('imglayer_1 - METHOD STARTED with parameters: request:%s' % request)
    
    image_id = "test" #TODO this needs to be DB identifier once there is a DB
    
    image = request.files['image']
    
    return_data = {
        'service':'imglayer',
        'version':1
    }
    
    ocr_response = ocr_adapter(image, image_id)
    
    if ocr_response['status'] == 'success':
        text = ocr_response['text']
        test_response = run_text_processes(text)
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

def run_text_processes(text):
    tags = []
    
    try:
        tags = nlp_adapter(text)
    except Exception, e:
        logger.error('datalayer_1 - ERROR the call the nlp service failed: %s' % e)
        if not MASK_ERRORS:
            raise e 
    
    locations = []
    
    try:
        locations = yahooplacemaker_adapter(text)
    except Exception, e:
        logger.error('datalayer_1 - ERROR the call the location service failed: %s' % e)
        if not MASK_ERRORS:
            raise e 
        
    return {'text':text, 'tags':tags, 'locations':locations }