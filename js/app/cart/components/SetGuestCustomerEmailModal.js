import React from 'react'
import { withTranslation } from 'react-i18next'
import Modal from 'react-modal'
import { connect } from 'react-redux'
import { closeSetPlayerEmailModal, setPlayer } from '../redux/actions'
import { Formik } from 'formik'

const SetGuestCustomerEmailModal = ({ isOpen, closeInvitePeopleToOrderModal, t, setGuestCustomer }) => {

  const initialValues = {
    email: '',
    name: ''
  }

  return (
    <Modal
      isOpen={ isOpen }
      onRequestClose={ () => closeInvitePeopleToOrderModal() }
      contentLabel={ t('INVITE_PEOPLOE_TO_ORDER_MODAL_LABEL') }
      className="ReactModal__Content--invite-people-to-order">
      <Formik
        initialValues={ initialValues }
        // validate={ this._validate }
        onSubmit={ (values) => {
          setGuestCustomer(values.email, values.name)
        }}
        validateOnBlur={ false }
        validateOnChange={ false }>
        {({
          values,
          errors,
          touched,
          handleSubmit,
          handleChange,
          handleBlur,
        }) => (
          <div>
            <form onSubmit={ handleSubmit } autoComplete="off" className="modal-body form">
              <div className={ errors.name && touched.name ? 'form-group has-error' : 'form-group' }>
                <label className="control-label" htmlFor="name">{ t('GROUP_ORDER_PLAYER_NAME_LABEL') }</label>
                <input type="text" name="name" className="form-control" autoComplete="off"
                       onChange={ handleChange }
                       onBlur={ handleBlur }
                       value={ values.name }
                       placeholder={ t('GROUP_ORDER_PLAYER_NAME_PLACEHOLDER') } />
                <label className="control-label" htmlFor="email">{ t('GROUP_ORDER_PLAYER_EMAIL_LABEL') }</label>
                <input type="email" name="email" className="form-control" autoComplete="off"
                  onChange={ handleChange }
                  onBlur={ handleBlur }
                  value={ values.email }
                  placeholder={ t('GROUP_ORDER_GUEST_PLAYER_PLACEHOLDER') } />
                <span className="help-block">Yop</span>
              </div>
              <button type="submit" className="btn btn-md btn-block btn-primary">{ t('ADHOC_ORDER_SAVE') }</button>
            </form>
          </div>
        )}
      </Formik>
    </Modal>
  )
}

function mapStateToProps(state) {

  return {
    isOpen: state.isSetPlayerEmailModalOpen,
  }
}

function mapDispatchToProps(dispatch) {

  return {
    closeSetGuestCustomerEmailModal: () => dispatch(closeSetPlayerEmailModal()),
    setGuestCustomer: (email, name) => dispatch(setPlayer({email, name})),
  }
}

export default connect(mapStateToProps, mapDispatchToProps)(withTranslation()(SetGuestCustomerEmailModal))
