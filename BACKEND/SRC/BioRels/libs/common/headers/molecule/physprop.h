#ifndef PHYSPROP_H
#define PHYSPROP_H
#include <stdint.h>
#include <cstdint>
namespace protspace
{
///
/// \brief The PhysProp class describe the physicochemical properties of an object (Atom, Box)
/// \author DESAPHY Jeremy
/// \date 12-01-16
///TODO Add boundary check for properties
/// For a list of allowed properties, see CHEMPROP namespace
///
class PhysProp
{
protected:
    ///
    /// \brief List of the physicochemical properties of the object
    ///
    ///  For a list of allowed properties, see CHEMPROP namespace
    ///
    uint16_t mProps;



public:
    ///
    /// \brief Standard constructor
    ///
    PhysProp():mProps(0x00){}

    ///
    /// \brief Copy constructor
    /// \param prop Property to be copied
    ///
    PhysProp(const PhysProp& prop)  { mProps=prop.mProps; }

    PhysProp& operator=(const PhysProp& prop) { mProps=prop.mProps;return *this;}

    ///
    /// \brief Remove all properties
    ///
    inline void clear(){ mProps=0x00;}

    bool empty()const {return (mProps==0x00);}

    ///
    /// \brief Add a new property to the object
    /// \param value Property to be added
    /// \note  For a list of allowed properties, see INTER namespace
    ///
    inline void addProperty(const uint16_t& value){mProps|=value;}

    inline void removeProperty(const uint16_t& value){mProps^=value;}

    ///
    /// \brief Check whether the object has the given property
    /// \param value Property to be checked
    /// \return True when the property is indeed in the object,false otherwise
    ///
   inline bool hasProperty(const uint16_t& value)const{return ((mProps&value)== value);}


    bool operator==(const PhysProp& prop)
    {
        return (prop.mProps==mProps);
    }
    inline const uint16_t& getPropValue()const {return mProps;}
};

}
#endif // PHYSPROP_H
