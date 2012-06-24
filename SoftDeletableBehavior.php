<?php
/**
 * Provides soft-deleting of records
 * 
 * @property string $softDeleteColumn The attribute which indicates a soft-delete
 * @property array $softDeleteValue Two item array containing the [false,true] values for soft-deletion. Defaults to array(0,1) ('false' and 'true' respectively).
 * 
 */
class SoftDeletableBehavior extends CActiveRecordBehavior
{
    //********************************************************************************
    // Variables
    //********************************************************************************

    /**
    * If defined, flag the value as deleted
    * @var string
    */
    public $deletedColumn = 'deleted';

    /**
    * If defined, set the date of the delete
    * @var string
    */
    public $dateColumn = 'dt_deleted';
    
    /**
    * Soft delete indicator [false,true]
    * @var array
    */
    protected $softDeleteValues = array( 0, 1 );   
    
    //********************************************************************************
    // Handlers
    //********************************************************************************
    
    /**
    * Soft deletes models that have the behavior
    * @params CEvent $oEvent
    * @returns boolean
    */
    public function beforeDelete( CEvent $oEvent )
    {
        parent::beforeDelete( $oEvent );
        
        if ( $oEvent->isValid && ! $oEvent->handled )
        {
            if ( $this->deletedColumn )
            {
                if ( $oEvent->sender->hasAttribute( $this->deletedColumn ) )
                {
                    $oEvent->isValid = false;
                    $oEvent->handled = true;
                    $oEvent->sender->setAttribute( $this->deletedColumn, $this->softDeleteValues[ 1 ] );
                    if ( ! $oEvent->sender->update( array( $this->deletedColumn ) ) )
                    {
                        throw new CDbException( 'Could not perform the soft-delete' );
                    }
                }
            }
            
            if ( $this->dateColumn )
            {
                if ( $oEvent->sender->hasAttribute( $this->dateColumn ) )
                {
                    $oEvent->isValid = false;
                    $oEvent->handled = true;
                    $oEvent->sender->setAttribute( $this->dateColumn, new CDbExpression('NOW()') );
                    if ( ! $oEvent->sender->update( array( $this->dateColumn ) ) )
                        throw new CDbException( 'Could not save the soft-delete date' );
                }
            }
        }
    }
    
    /**
    * Insert our soft-delete criteria
    * @param CEvent $oEvent
    */
    public function beforeFind( CEvent $oEvent )
    {
        if ( $this->deletedColumn && $this->owner->hasAttribute( $this->deletedColumn ) ) 
        {
            //      Merge in the soft delete indicator
            $oEvent->sender->getDbCriteria()->mergeWith(
                array( 
                    'condition' => $this->deletedColumn . ' = :softDeleteValue', 
                    'params' => array( ':softDeleteValue' => $this->softDeleteValues[ 0 ] ),
                )
            );
        }

        //      Pass it on...
        return parent::beforeFind( $oEvent );
    }
        
    //********************************************************************************
    //* Public Methods
    //********************************************************************************
    
    /**
    * Undeletes a soft-deleted model
    * 
    * @returns boolean
    */
    public function undelete()
    {
        if ( $this->deletedColumn )
        {
            //      Perform a soft delete if this model allows
            if ( $this->hasAttribute( $this->deletedColumn ) )
            {
                $this->setAttribute( $this->deletedColumn, $this->softDeleteValues[ 0 ] );
                return $this->update( array( $this->deletedColumn ) );
            }
        }
        
        //      Otherwise, not possible
        return false;
    }
        
}
?>