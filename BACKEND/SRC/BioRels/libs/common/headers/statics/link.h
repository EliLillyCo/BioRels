#ifndef LINK_H
#define LINK_H

#include "headers/statics/ids.h"
#include "headers/statics/protExcept.h"

namespace protspace
{

template<class T,  class T2> class link:public ids
{

protected:
    /**
     * @brief Parent of the link (i.e. Graph for Edge and MacroMole for Atom)
     */
    const T& mParent;

    /**
     * @brief First dot involved in the link
     */
    T2& mDot1;

    /**
     * @brief Second dot involved in the link
     */
    T2& mDot2;


public:

    /**
     * @brief Standard constructor
     * @param Parent object
     * @param MId Id as given by the program
     * @param FId Id as given by the input file
     * @param dot1 Reference of the first dot involved in this link
     * @param dot2 Reference of the second dot involved in this link
     *
     * Create a new link object that is part of the parent object
     * and links dot1 and dot2
     *
     */
    link( const T& Parent,
          const int& MId,
          const int& FId,
          T2& dot1,
          T2& dot2);


    /**
     * @brief Given one dot, returns the other dot
     * @param param Dot to check for the other dot
     * @return The other dot of this link
     * @throw 010101 - Given param is not part of this link
     * @test testEdgeCreation
     */
    T2* getOther( T2*const param) const throw(ProtExcept);


    /**
     * @brief Given one dot, returns the other dot of this link
     * @param param Dot to check for the other dot
     * @return The other dot of this link
     * @throw 010201 - Given param is not part of this link
     * @test testEdgeCreation
     */
    T2& getOther(const T2& param) const throw(ProtExcept);

    /**
     * @brief Gives the first dot involved in this link
     * @return Reference of the first dot involved in this link
     * @test testEdgeCreation
     */
    T2& getDot1() const {return mDot1;}

    /**
     * @brief Gives the second dot involved in this link
     * @return Reference of the second dot involved in this link
     * @test testEdgeCreation
     */
    T2& getDot2() const {return mDot2;}


    /**
     * @brief Return the parent object managing this link
     * @return Parent object
     * @test testEdgeCreation
     */
    const T& getParent() const {return mParent;}


};


template<class T,  class T2> protspace::link<T,T2>::
link( const T& Parent,
      const int& MId,
      const int& FId,
      T2& dot1,
      T2& dot2)
     :ids(MId,FId),
      mParent(Parent),
      mDot1(dot1),
      mDot2(dot2)
{

    mDot1.add(mDot2.getMID(),mMId);
    mDot2.add(mDot1.getMID(),mMId);

}

template<class T,  class T2> T2 *
protspace::link<T, T2>::getOther( T2* const param) const throw(ProtExcept)
{
    if (param==&mDot1) return &mDot2;
    else if (param==&mDot2) return &mDot1;
    throw_line("010101",
                     "link::getOther",
                     "Given parameter is not part of this link");

}
template<class T,  class T2> T2&
protspace::link<T, T2>::getOther( const T2& param) const throw(ProtExcept)
{
    if (&param==&mDot1) return mDot2;
    else if (&param==&mDot2) return mDot1;
    throw_line("010201",
                     "link::getOther",
                     "Given parameter is not part of this link");

}










}
#endif // LINK_H

