from application import logger
from configuration import *
from adapters import nlp_adapter, ocr_adapter, yahooplacemaker_adapter

def datalayer_1(request):
    
    logger.info('datalayer_1 - METHOD STARTED with parameters: request:%s' % request)
    
    return_data = { 'service':'dataLayer', 'version':1 }
    
    if 'text' not in request.form:
        logger.error('datalayer_1 - ERROR post variable \'text\' not supplied')
        return ERROR_DATALAYER_NOTEXT
    
    text = request.form['text']
    
    return_data = run_text_processes(text)
    
    return_data['service'] = 'datalayer'
    return_data['version'] = 1
    
    logger.info('datalayer_1 - METHOD ENDED')
    
    return return_data

def imglayer_1(request):
    
    logger.info('imglayer_1 - METHOD STARTED with parameters: request:%s' % request)
    
    image_id = "test" #TODO this needs to be DB identifier once there is a DB
    
    image = request.files['image']
    
    text = ocr_adapter(image, image_id)
    
    return_data = run_text_processes(text)

    return_data['service'] = 'imglayer'
    return_data['version'] = 1
    
    logger.info('imglayer_1 - METHOD ENDED')
    
    return return_data

def run_text_processes(text):
    tags = []
    
    try:
        tags = nlp_adapter(text)
    except Exception, e:
        logger.error('datalayer_1 - ERROR the call the nlp service failed: %s' % e)
        if MASK_ERRORS:
            return ERROR_DATALAYER_NLPSERVICE
        raise e 
    
    locations = []
    
    try:
        locations = yahooplacemaker_adapter(text)
    except Exception, e:
        logger.error('datalayer_1 - ERROR the call the location service failed: %s' % e)
        if MASK_ERRORS:
            return ERROR_DATALAYER_LOCATIONSERVICE
        raise e 
        
    return_data = {}
    return_data['status'] = 'success'
    return_data['code'] = 0;
    return_data['response'] = { 
        'tags':tags,
        'locations':locations,
        'text':text
    }
    
    return return_data